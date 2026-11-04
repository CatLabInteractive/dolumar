<?php

require_once __DIR__ . '/../NewMap/FakeDatabase.php';

/**
 * Lock double: hands out each lock once, like Neuron_Core_Lock, without the n_locks table.
 */
class ProcessQueueFakeLock extends Neuron_Core_Lock
{
	public $held = [];
	public $released = [];

	public function setLock ($sType, $id, $iTimeout = 300)
	{
		$key = $sType . ':' . $id;
		if (isset ($this->held[$key])) {
			return false;
		}
		$this->held[$key] = true;
		return true;
	}

	public function releaseLock ($sType, $id)
	{
		$key = $sType . ':' . $id;
		unset ($this->held[$key]);
		$this->released[] = $key;
	}
}

/**
 * Premium with the database, lock and action handlers replaced, so the queue processing can be checked on its own.
 */
class ProcessQueueTestPremium extends Dolumar_Players_Village_Premium
{
	public $db;
	public $lock;
	public $results = [];
	public $executed = [];

	protected function getDatabase () { return $this->db; }
	protected function getLock () { return $this->lock; }

	public function executeQueuedAction ($sAction, $aData)
	{
		echo "handler output\n";
		$this->executed[] = $aData['n'];
		return $this->results[$aData['n']] ?? true;
	}
}

class ProcessQueueTest extends PHPUnit\Framework\TestCase
{
	private function village ($vacation = false, $active = true)
	{
		$owner = new class ($vacation) {
			public function __construct (private $vacation) {}
			public function inVacationMode () { return $this->vacation; }
		};

		return new class ($owner, $active) {
			public function __construct (private $owner, private $active) {}
			public function getId () { return 42; }
			public function isActive () { return $this->active; }
			public function getOwner () { return $this->owner; }
		};
	}

	private function premium ($village, array $rows)
	{
		$premium = new ProcessQueueTestPremium ($village);
		$premium->db = new FakeDatabase (['FROM' => $rows]);
		$premium->lock = new ProcessQueueFakeLock ();
		return $premium;
	}

	private function row ($id, $n)
	{
		return ['pq_id' => $id, 'pq_action' => 'build', 'pq_data' => json_encode (['n' => $n])];
	}

	public function testRunsQueuedActionsOldestFirstAndRemovesTheStartedOnes ()
	{
		$premium = $this->premium ($this->village (), [$this->row (7, 'a'), $this->row (9, 'b')]);
		$premium->results = ['a' => true, 'b' => false];

		$this->assertSame (1, $premium->processQueue ());
		$this->assertSame (['a', 'b'], $premium->executed);

		$sql = implode ("\n", $premium->db->queries);
		$this->assertStringContainsString ('ORDER BY', $sql);
		$this->assertMatchesRegularExpression ('/DELETE FROM\s+premium_queue\s+WHERE\s+pq_id = 7/', $sql);
		$this->assertMatchesRegularExpression ('/UPDATE\s+premium_queue\s+SET\s+pq_lastcheck = FROM_UNIXTIME\(\d+\)\s+WHERE\s+pq_id = 9/', $sql);
		$this->assertSame (['premium_queue:42'], $premium->lock->released);
	}

	public function testDoesNotEchoIntoAWebResponse ()
	{
		// Under PHPUnit PHP_SAPI is cli, so the handler output is passed through; it must never be left in an
		// output buffer of its own.
		$premium = $this->premium ($this->village (), [$this->row (7, 'a')]);
		$level = ob_get_level ();

		ob_start ();
		$premium->processQueue ();
		$out = ob_get_clean ();

		$this->assertSame ($level, ob_get_level ());
		$this->assertStringContainsString ('handler output', $out);
	}

	public function testEmptyQueueTakesNoLock ()
	{
		$premium = $this->premium ($this->village (), []);

		$this->assertSame (0, $premium->processQueue ());
		$this->assertSame ([], $premium->lock->released);
		$this->assertCount (1, $premium->db->queries);
	}

	public function testSkipsWhenAnotherRequestHoldsTheLock ()
	{
		$premium = $this->premium ($this->village (), [$this->row (7, 'a')]);
		$premium->lock->setLock ('premium_queue', 42);

		$this->assertSame (0, $premium->processQueue ());
		$this->assertSame ([], $premium->executed);
	}

	public function testVacationModeDropsTheQueueWithoutRunningIt ()
	{
		// Same as the old cron behaviour: queues of inactive villages or players on vacation are removed.
		$premium = $this->premium ($this->village (true), [$this->row (7, 'a')]);

		$this->assertSame (1, $premium->processQueue ());
		$this->assertSame ([], $premium->executed);
	}

	public function testReleasesTheLockWhenAHandlerThrows ()
	{
		$village = $this->village ();
		$premium = new class ($village) extends ProcessQueueTestPremium {
			public function executeQueuedAction ($sAction, $aData) { throw new RuntimeException ('boom'); }
		};
		$premium->db = new FakeDatabase (['FROM' => [$this->row (7, 'a')]]);
		$premium->lock = new ProcessQueueFakeLock ();

		try {
			$premium->processQueue ();
			$this->fail ('Expected the exception to propagate');
		} catch (RuntimeException $e) {
			$this->assertSame (['premium_queue:42'], $premium->lock->released);
		}
	}
}
