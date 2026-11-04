/*
	Battle report replay player.

	The report template (battle/report/view.phpt) puts the replay data on div.br-replay[data-replay]
	(built by Dolumar_Battle_ReportView::getReplayData): slots, tokens (one per unit on the field),
	frames (per token [slot id, alive, status]) and events (caption per frame).
	Game.dolumar.initBattleWindow calls DolumarBattleReplay.init on every window update; a live fight is
	re-rendered by the 15 second poll, so the player keeps its position per report between renders.
*/
var DolumarBattleReplay = (function ()
{
	var states = {};

	function el (tag, cls, text)
	{
		var e = document.createElement (tag);
		if (cls) { e.className = cls; }
		if (text !== undefined && text !== null) { e.textContent = text; }
		return e;
	}

	function Player (root, data)
	{
		this.root = root;
		this.data = data;
		this.frame = 0;
		this.timer = null;
		this.speed = 1;

		var saved = states[data.report];
		if (saved)
		{
			this.speed = saved.speed;
			// A live fight continues where the player was, then plays the new events.
			this.frame = Math.min (saved.frame, data.frames.length - 1);
		}
		else
		{
			this.frame = Math.min (data.start, data.frames.length - 1);
		}

		this.build ();
		this.show (this.frame, false);

		if (saved && saved.playing)
		{
			this.play ();
		}
		else if (data.live && this.frame < data.frames.length - 1)
		{
			this.play ();
		}
	}

	Player.prototype.build = function ()
	{
		var self = this;
		var data = this.data;
		var field = this.root.querySelector ('.br-slots');

		this.slotEls = {};
		this.tokenEls = [];

		for (var i = 0; i < data.slots.length; i ++)
		{
			var slot = el ('div', 'br-slot');
			if (data.slots[i].img) { slot.style.backgroundImage = "url('" + data.slots[i].img + "')"; }
			slot.appendChild (el ('span', 'br-sl', data.slots[i].name));
			field.appendChild (slot);
			this.slotEls[data.slots[i].id] = slot;
		}

		for (var t = 0; t < data.tokens.length; t ++)
		{
			var token = data.tokens[t];
			var tok = el ('div', 'br-tok ' + token.team + ' ' + token.side);
			var img = el ('img');
			img.src = token.img;
			img.alt = '';
			img.title = token.name;
			tok.appendChild (img);
			tok.appendChild (el ('span', 'br-cnt', ''));
			var hp = el ('div', 'br-hp');
			hp.appendChild (el ('i'));
			tok.appendChild (hp);
			tok.appendChild (el ('span', 'br-flag', ''));
			this.tokenEls.push (tok);
		}

		// Starting headcounts, for the strength bars.
		this.startCounts = [];
		var first = data.frames[0].s;
		for (var s = 0; s < first.length; s ++)
		{
			this.startCounts.push (Math.max (1, first[s][1]));
		}

		// Scrubber: one segment per phase or round.
		var segs = this.root.querySelector ('.br-segs');
		var names = this.root.querySelector ('.br-names');
		this.segEls = [];
		var total = data.frames.length;
		for (var p = 0; p < data.phases.length; p ++)
		{
			var from = data.phases[p].frame;
			var to = p + 1 < data.phases.length ? data.phases[p + 1].frame : total;
			var seg = el ('div');
			seg.style.flex = String (Math.max (1, to - from));
			seg.title = data.phases[p].title;
			seg.appendChild (el ('i'));
			seg.onclick = (function (frame) { return function () { self.pause (); self.show (frame, true); }; }) (from);
			segs.appendChild (seg);
			var name = el ('div', null, data.phases[p].label);
			name.style.flex = seg.style.flex;
			names.appendChild (name);
			this.segEls.push ({ el: seg, from: from, to: to });
		}

		this.playButton = this.root.querySelector ('.br-play');
		this.playButton.onclick = function () { if (self.timer) { self.pause (); } else { self.play (); } };
		this.root.querySelector ('.br-prev').onclick = function () { self.pause (); self.show (Math.max (0, self.frame - 1), true); };
		this.root.querySelector ('.br-next').onclick = function () { self.pause (); self.show (Math.min (total - 1, self.frame + 1), true); };

		var speed = this.root.querySelector ('.br-speed');
		speed.textContent = this.speed + '×';
		speed.onclick = function ()
		{
			self.speed = self.speed === 1 ? 2 : (self.speed === 2 ? 4 : 1);
			speed.textContent = self.speed + '×';
			self.save ();
			if (self.timer) { self.pause (); self.play (); }
		};
	};

	Player.prototype.show = function (index, animate)
	{
		var data = this.data;
		var frame = data.frames[index];
		var event = data.events[index];
		this.frame = index;

		for (var t = 0; t < this.tokenEls.length; t ++)
		{
			var tok = this.tokenEls[t];
			var state = frame.s[t];
			var slot = this.slotEls[state[0]];
			var status = state[2];

			if (slot && tok.parentNode !== slot) { slot.appendChild (tok); }

			tok.className = 'br-tok ' + data.tokens[t].team + ' ' + data.tokens[t].side + (status ? ' ' + status : '');
			tok.querySelector ('.br-cnt').textContent = state[1];

			var pct = Math.round (state[1] / this.startCounts[t] * 100);
			var bar = tok.querySelector ('.br-hp i');
			bar.style.width = pct + '%';
			bar.className = pct < 40 ? 'low' : '';

			var flag = tok.querySelector ('.br-flag');
			flag.textContent = status === 'fled' || status === 'wiped' ? data.labels[status] : '';
			flag.style.display = flag.textContent ? '' : 'none';

			var old = tok.querySelector ('.br-float');
			if (old) { old.parentNode.removeChild (old); }
			if (animate && frame.t === t && event && event.kills)
			{
				tok.appendChild (el ('span', 'br-float', event.kills));
			}
		}

		var caption = this.root.querySelector ('.br-caption');
		caption.querySelector ('.br-ph').textContent = event ? event.phase : '';
		caption.querySelector ('.br-tx').innerHTML = event ? event.text : '';
		var kills = caption.querySelector ('.br-kills');
		kills.textContent = event && event.kills ? event.kills : '';
		kills.className = 'br-kills' + (event && event.kills ? (event.side === 'us' ? ' ui-bad' : ' ui-good') : '');

		for (var s = 0; s < this.segEls.length; s ++)
		{
			var seg = this.segEls[s];
			var fill = index >= seg.to ? 100 : (index < seg.from ? 0 : Math.round ((index - seg.from + 1) / (seg.to - seg.from) * 100));
			seg.el.firstChild.style.width = fill + '%';
		}

		this.save ();
	};

	Player.prototype.play = function ()
	{
		var self = this;
		if (this.frame >= this.data.frames.length - 1 && !this.data.live)
		{
			this.frame = 0;
		}
		this.playButton.textContent = this.data.labels.pause;
		clearInterval (this.timer);
		this.timer = setInterval (function ()
		{
			if (!document.body.contains (self.root))
			{
				clearInterval (self.timer);
				return;
			}
			if (self.frame >= self.data.frames.length - 1)
			{
				// Live: wait for the next poll to bring new events.
				if (!self.data.live) { self.pause (); }
				return;
			}
			self.show (self.frame + 1, true);
		}, 1400 / this.speed);
		this.save ();
	};

	Player.prototype.pause = function ()
	{
		clearInterval (this.timer);
		this.timer = null;
		this.playButton.textContent = this.data.labels.play;
		this.save ();
	};

	Player.prototype.save = function ()
	{
		states[this.data.report] = { frame: this.frame, playing: !!this.timer, speed: this.speed };
	};

	return {
		'init' : function (container)
		{
			if (!container || !container.querySelectorAll) { return; }
			var roots = container.querySelectorAll ('div.br-replay[data-replay]');
			for (var i = 0; i < roots.length; i ++)
			{
				if (roots[i].brPlayer) { continue; }
				try
				{
					roots[i].brPlayer = new Player (roots[i], JSON.parse (roots[i].getAttribute ('data-replay')));
				}
				catch (e)
				{
					if (window.console) { console.error (e); }
				}
			}
		}
	};
}) ();
