/**
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2019 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 *
 * Network Diagram JavaScript - Community Contribution
 * Copyright (C) 2026 podheitor
 * Renders a graphical network topology of Bacula components
 * with status indicators (green/yellow/red)
 */

/**
 * Network Diagram module.
 * Data is passed from PHP via DIAGRAM_DATA global variable.
 *
 * @author podheitor
 */
const NetworkDiagram = {
	nodes: [],
	edges: [],

	/**
	 * Initialize the diagram.
	 */
	init() {
		console.log('NetworkDiagram init, DIAGRAM_DATA:', DIAGRAM_DATA);
		if (typeof DIAGRAM_DATA === 'undefined' || !DIAGRAM_DATA) {
			console.error('DIAGRAM_DATA not defined');
			return;
		}
		this.render(DIAGRAM_DATA);
	},

	/**
	 * Render the network diagram.
	 *
	 * @param {Object} data diagram data from PHP
	 */
	render(data) {
		const svg = document.getElementById('netdiagram_svg');
		if (!svg) {
			console.error('SVG element not found');
			return;
		}

		const dir = data.director || {};
		const dir_ip = window.location.hostname || '127.0.0.1';

		console.log('Rendering with data:', data);

		this.nodes = [];
		this.edges = [];

		// Bacularis Web (far left)
		this.nodes.push({
			id: 'bacularis', label: 'Bacularis', sub: 'Web UI',
			ip: dir_ip, port: '9097', status: 'green', type: 'web'
		});
		this.edges.push({ from: 'bacularis', to: 'director', label: 'Web' });

		// Director (center)
		const dir_status = dir.status || (dir.version ? 'green' : 'gray');
		this.nodes.push({
			id: 'director',
			label: dir.name || 'bacula-dir',
			sub: dir.version || 'Unknown',
			ip: dir_ip, port: '9101',
			status: dir_status, type: 'director'
		});

		// PostgreSQL (top-center)
		this.nodes.push({
			id: 'postgres', label: 'PostgreSQL', sub: 'Catalog DB',
			ip: '127.0.0.1', port: '5432', status: 'green', type: 'database'
		});
		this.edges.push({ from: 'director', to: 'postgres', label: 'SQL' });

		// Clients (bottom-left)
		const clients = data.clients || [];
		if (clients.length === 0) {
			this.nodes.push({
				id: 'c0', label: 'localhost-fd', sub: 'File Daemon',
				ip: '127.0.0.1', port: '9102', status: 'green', type: 'client'
			});
			this.edges.push({ from: 'director', to: 'c0', label: 'FD' });
		} else {
			clients.forEach((c, i) => {
				this.nodes.push({
					id: 'c' + i, label: c.name || ('Client ' + (i + 1)),
					sub: 'File Daemon', ip: c.addr || '127.0.0.1', port: '9102',
					status: c.status || 'green', type: 'client'
				});
				this.edges.push({ from: 'director', to: 'c' + i, label: 'FD' });
			});
		}

		// Storages (bottom-right)
		const storages = data.storages || [];
		if (storages.length === 0) {
			this.nodes.push({
				id: 's0', label: 'File1', sub: 'Storage Daemon',
				ip: '127.0.0.1', port: '9103', status: 'green', type: 'storage'
			});
			this.edges.push({ from: 'director', to: 's0', label: 'SD' });
		} else {
			storages.forEach((s, i) => {
				this.nodes.push({
					id: 's' + i, label: s.name || ('Storage ' + (i + 1)),
					sub: 'Storage Daemon', ip: s.addr || '127.0.0.1', port: '9103',
					status: s.status || 'green', type: 'storage'
				});
				this.edges.push({ from: 'director', to: 's' + i, label: 'SD' });
			});
		}

		// Pools (right side)
		const pools = data.pools || [];
		if (pools.length > 0) {
			const pool_names = pools.map(p => p.name).join(', ');
			this.nodes.push({
				id: 'pools', label: 'Pools (' + pools.length + ')',
				sub: pool_names.substring(0, 25) + (pool_names.length > 25 ? '…' : ''),
				ip: '', port: '', status: 'green', type: 'pool'
			});
			this.edges.push({ from: 'director', to: 'pools', label: 'Pool' });
		}

		const W = svg.clientWidth || 1000;
		const H = svg.clientHeight || 620;
		console.log('Layout:', W, 'x', H);
		this.layout(W, H);
		this.draw(svg);
	},

	/**
	 * Calculate node positions.
	 *
	 * @param {number} W SVG width
	 * @param {number} H SVG height
	 */
	layout(W, H) {
		const pos = (id) => this.nodes.find(n => n.id === id);
		const dir = pos('director');
		const pg = pos('postgres');
		const web = pos('bacularis');
		const pools = pos('pools');
		const clients = this.nodes.filter(n => n.type === 'client');
		const storages = this.nodes.filter(n => n.type === 'storage');

		if (dir) { dir.x = W / 2; dir.y = H / 2; }
		if (pg) { pg.x = W / 2; pg.y = 55; }
		if (web) { web.x = 80; web.y = H / 2; }
		if (pools) { pools.x = W - 100; pools.y = H / 2; }

		const c_count = clients.length || 1;
		clients.forEach((c, i) => {
			const spacing = c_count > 1 ? Math.min(200, (W * 0.35) / (c_count - 1)) : 0;
			c.x = c_count > 1 ? W * 0.25 + (i - (c_count - 1) / 2) * spacing : W * 0.25;
			c.y = H - 85;
		});

		const s_count = storages.length || 1;
		storages.forEach((s, i) => {
			const spacing = s_count > 1 ? Math.min(200, (W * 0.35) / (s_count - 1)) : 0;
			s.x = s_count > 1 ? W * 0.75 + (i - (s_count - 1) / 2) * spacing : W * 0.75;
			s.y = H - 85;
		});
	},

	/**
	 * Get status color.
	 *
	 * @param {string} status status name
	 * @return {string} hex color
	 */
	sc(s) {
		return { green: '#4caf50', yellow: '#ff9800', red: '#f44336' }[s] || '#555';
	},

	/**
	 * Get status label.
	 *
	 * @param {string} status status name
	 * @return {string} label text
	 */
	sl(s) {
		return { green: 'Online', yellow: 'Degraded', red: 'Offline' }[s] || 'Unknown';
	},

	/**
	 * Get type icon emoji.
	 *
	 * @param {string} type node type
	 * @return {string} emoji
	 */
	ti(t) {
		return {
			director: '🖥️', database: '🗄️', client: '💻',
			storage: '💾', web: '🌐', pool: '📦'
		}[t] || '📦';
	},

	/**
	 * Draw SVG diagram.
	 *
	 * @param {SVGElement} svg SVG element
	 */
	draw(svg) {
		let h = `<defs>
			<marker id="ah" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto">
				<polygon points="0 0,10 3.5,0 7" fill="#555"/></marker>
			<filter id="gl"><feGaussianBlur stdDeviation="3" result="b"/>
				<feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
			<linearGradient id="nb" x1="0%" y1="0%" x2="0%" y2="100%">
				<stop offset="0%" style="stop-color:#2a2a4a"/>
				<stop offset="100%" style="stop-color:#1a1a2e"/>
			</linearGradient></defs>`;

		// Edges
		this.edges.forEach(e => {
			const f = this.nodes.find(n => n.id === e.from);
			const t = this.nodes.find(n => n.id === e.to);
			if (!f || !t) return;
			const mx = (f.x + t.x) / 2;
			const my = (f.y + t.y) / 2 - 10;
			h += `<line x1="${f.x}" y1="${f.y}" x2="${t.x}" y2="${t.y}" stroke="#444" stroke-width="2" stroke-dasharray="6,3" marker-end="url(#ah)"/>`;
			h += `<rect x="${mx - 16}" y="${my - 8}" width="32" height="16" rx="3" fill="#333"/>`;
			h += `<text x="${mx}" y="${my + 3}" text-anchor="middle" fill="#aaa" font-size="10" font-family="monospace">${e.label}</text>`;
		});

		const NW = 180, NH = 80;
		this.nodes.forEach(n => {
			const col = this.sc(n.status);
			const x = n.x - NW / 2;
			const y = n.y - NH / 2;

			h += `<rect x="${x}" y="${y}" width="${NW}" height="${NH}" rx="8" fill="url(#nb)" stroke="${col}" stroke-width="2" filter="url(#gl)"/>`;
			h += `<circle cx="${x + 14}" cy="${y + 14}" r="5" fill="${col}"/>`;
			h += `<text x="${x + 14}" y="${y + 42}" font-size="20">${this.ti(n.type)}</text>`;

			const lbl = n.label.length > 14 ? n.label.substring(0, 13) + '…' : n.label;
			h += `<text x="${x + 40}" y="${y + 24}" fill="#fff" font-size="12" font-weight="bold" font-family="sans-serif">${lbl}</text>`;

			const sub = (n.sub || '').length > 20 ? n.sub.substring(0, 19) + '…' : n.sub;
			h += `<text x="${x + 40}" y="${y + 40}" fill="#888" font-size="9" font-family="sans-serif">${sub || ''}</text>`;

			if (n.ip && n.port) {
				h += `<text x="${x + NW / 2}" y="${y + NH - 10}" text-anchor="middle" fill="#6af" font-size="10" font-family="monospace">${n.ip}:${n.port}</text>`;
			}

			h += `<text x="${x + NW / 2}" y="${y + 14}" text-anchor="middle" fill="${col}" font-size="9" font-weight="bold" font-family="sans-serif">${this.sl(n.status)}</text>`;
		});

		svg.innerHTML = h;
		console.log('Diagram rendered with', this.nodes.length, 'nodes and', this.edges.length, 'edges');
	}
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	console.log('DOMContentLoaded - DIAGRAM_DATA:', typeof DIAGRAM_DATA, DIAGRAM_DATA);
	if (typeof DIAGRAM_DATA !== 'undefined') {
		NetworkDiagram.init();
	} else {
		console.error('DIAGRAM_DATA not defined on page load');
	}
});
