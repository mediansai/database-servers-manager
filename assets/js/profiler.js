/**
 * Query Profiler — front-end logic
 */

let profilerDatabase = document.getElementById('profiler-database')
    ? document.getElementById('profiler-database').value
    : null;

const PROFILER_ACCESS_COLORS = {
    system:      { bg: '#dcfce7', text: '#166534', label: 'system' },
    const:       { bg: '#dcfce7', text: '#166534', label: 'const' },
    eq_ref:      { bg: '#dcfce7', text: '#166534', label: 'eq_ref' },
    ref:         { bg: '#dbeafe', text: '#1e40af', label: 'ref' },
    range:       { bg: '#fef9c3', text: '#854d0e', label: 'range' },
    index_merge: { bg: '#fef9c3', text: '#854d0e', label: 'index_merge' },
    index:       { bg: '#fef3c7', text: '#92400e', label: 'index' },
    ALL:         { bg: '#fee2e2', text: '#991b1b', label: 'ALL (full scan)' },
};

function onProfilerDatabaseChange() {
    profilerDatabase = document.getElementById('profiler-database').value;
}

/* ---------------------------------------------------------------- */
/* Tabs                                                              */
/* ---------------------------------------------------------------- */

function showProfilerTab(tab) {
    document.querySelectorAll('.profiler-tab').forEach(el => el.classList.add('hidden'));
    document.getElementById('profiler-tab-' + tab).classList.remove('hidden');

    document.querySelectorAll('.profiler-tab-btn').forEach(btn => {
        const active = btn.dataset.tab === tab;
        btn.classList.toggle('bg-indigo-50', active);
        btn.classList.toggle('text-indigo-700', active);
        btn.classList.toggle('text-gray-600', !active);
    });

    if (tab === 'slow' && !document.getElementById('profiler-slow-results').dataset.loaded) {
        loadSlowQueries();
    }
    if (tab === 'history' && !document.getElementById('profiler-history-results').dataset.loaded) {
        loadProfilerHistory();
    }
}

/* ---------------------------------------------------------------- */
/* Profile a query                                                   */
/* ---------------------------------------------------------------- */

function runProfiledQuery() {
    const query = document.getElementById('profiler-query').value.trim();
    const resultsDiv = document.getElementById('profiler-run-results');

    if (!query) {
        showToast('Enter a query to profile', 'error');
        return;
    }
    if (!profilerDatabase) {
        showToast('Select a database first', 'error');
        return;
    }

    resultsDiv.innerHTML = '<div class="flex items-center justify-center py-12"><div class="loader"></div></div>';

    fetch('handlers/profiler_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'profile_query', database: profilerDatabase, query })
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            resultsDiv.innerHTML = profilerErrorBox(res.message);
            return;
        }
        resultsDiv.innerHTML = renderProfileResult(res.data);
    })
    .catch(() => {
        resultsDiv.innerHTML = profilerErrorBox('Request failed');
    });
}

function profilerErrorBox(message) {
    return `<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm flex items-start gap-2">
        <i class="fas fa-exclamation-circle mt-0.5"></i>
        <span>${escapeHtml(message || 'Something went wrong')}</span>
    </div>`;
}

function renderProfileResult(d) {
    if (d.error) {
        return profilerErrorBox(d.error);
    }

    const indexBadges = (d.indexes_used && d.indexes_used.length)
        ? d.indexes_used.map(k => `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-mono mr-1 mb-1"><i class="fas fa-key text-[10px]"></i>${escapeHtml(k)}</span>`).join('')
        : '<span class="text-gray-400 text-sm italic">No index used</span>';

    const metrics = `
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        ${profilerMetricCard('Execution Time', d.execution_time_ms + ' ms', 'fa-stopwatch', d.execution_time_ms > 500 ? 'text-red-600' : 'text-indigo-600')}
        ${profilerMetricCard('Rows Scanned', profilerFmtNum(d.rows_scanned), 'fa-search', d.rows_scanned > 10000 ? 'text-red-600' : 'text-gray-800')}
        ${profilerMetricCard('Rows Returned', profilerFmtNum(d.rows_returned), 'fa-table', 'text-gray-800')}
        ${profilerMetricCard('Full Table Scans', d.full_table_scans, 'fa-triangle-exclamation', d.full_table_scans > 0 ? 'text-red-600' : 'text-green-600')}
    </div>`;

    const indexSection = `
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-2">Indexes Used</h3>
        <div>${indexBadges}</div>
    </div>`;

    const explainTree = `
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">EXPLAIN Visualization</h3>
        ${d.explain_json ? renderExplainTree(d.explain_json) : '<p class="text-sm text-gray-400 italic">EXPLAIN plan not available for this query.</p>'}
    </div>`;

    const classicTable = (d.explain_classic && d.explain_classic.length) ? `
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6 overflow-x-auto">
        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Classic EXPLAIN</h3>
        ${profilerTable(d.explain_classic)}
    </div>` : '';

    const previewTable = (d.preview_rows && d.preview_rows.length) ? `
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 overflow-x-auto">
        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Result Preview (first ${d.preview_rows.length})</h3>
        ${profilerTable(d.preview_rows)}
    </div>` : '';

    return metrics + indexSection + explainTree + classicTable + previewTable;
}

function profilerMetricCard(label, value, icon, colorClass) {
    return `<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div class="flex items-center gap-2 text-gray-400 text-xs font-semibold uppercase mb-1">
            <i class="fas ${icon}"></i> ${label}
        </div>
        <div class="text-2xl font-bold ${colorClass}">${value}</div>
    </div>`;
}

function profilerFmtNum(n) {
    if (n === null || n === undefined) return '—';
    return Number(n).toLocaleString();
}

function profilerTable(rows) {
    if (!rows.length) return '<p class="text-sm text-gray-400 italic">No rows</p>';
    const cols = Object.keys(rows[0]);
    let html = '<table class="w-full text-sm"><thead><tr class="border-b border-gray-200">';
    cols.forEach(c => html += `<th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">${escapeHtml(c)}</th>`);
    html += '</tr></thead><tbody class="divide-y divide-gray-100">';
    rows.forEach(row => {
        html += '<tr class="hover:bg-gray-50">';
        cols.forEach(c => {
            const v = row[c];
            html += `<td class="px-3 py-2 text-gray-700">${v !== null && v !== undefined ? escapeHtml(String(v)) : '<span class="text-gray-300 italic">NULL</span>'}</td>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    return html;
}

/* ---------------------------------------------------------------- */
/* EXPLAIN JSON -> visual tree                                       */
/* ---------------------------------------------------------------- */

// Keys that represent structural groupings worth their own labeled box
const PROFILER_GROUP_LABELS = {
    query_block: 'Query Block',
    nested_loop: 'Nested Loop Join',
    ordering_operation: 'Ordering',
    grouping_operation: 'Grouping',
    duplicates_removal: 'Duplicate Removal',
    materialized_from_subquery: 'Materialized Subquery',
    attached_subqueries: 'Attached Subqueries',
    union_result: 'Union',
};

function renderExplainTree(node, depth) {
    depth = depth || 0;
    if (!node || typeof node !== 'object') return '';

    let html = '';

    if (Array.isArray(node)) {
        node.forEach(item => { html += renderExplainTree(item, depth); });
        return html;
    }

    if (node.table && typeof node.table === 'object') {
        html += renderExplainTableBox(node.table, depth);
    }

    Object.keys(node).forEach(key => {
        if (key === 'table') return;
        const value = node[key];
        if (value && typeof value === 'object') {
            const label = PROFILER_GROUP_LABELS[key] || key;
            const inner = renderExplainTree(value, depth + 1);
            if (inner) {
                html += `<div class="explain-group" style="margin-left:${depth * 18}px; border-left:2px solid #e5e7eb; padding-left:12px; margin-top:6px;">
                    <div class="text-xs font-semibold text-gray-400 uppercase mb-1">${escapeHtml(label)}</div>
                    ${inner}
                </div>`;
            }
        }
    });

    return html;
}

function renderExplainTableBox(t, depth) {
    const accessType = t.access_type || 'ALL';
    const color = PROFILER_ACCESS_COLORS[accessType] || PROFILER_ACCESS_COLORS.ALL;
    const key = t.key || null;
    const rows = t.rows_examined_per_scan;
    const filtered = t.filtered;
    const possibleKeys = Array.isArray(t.possible_keys) ? t.possible_keys.join(', ') : (t.possible_keys || '—');
    const extra = [];
    if (t.using_index) extra.push('Using index');
    if (t.using_filesort) extra.push('Using filesort');
    if (t.using_temporary_table) extra.push('Using temporary table');
    if (t.attached_condition) extra.push('Has WHERE condition');

    return `<div style="margin-left:${depth * 18}px" class="rounded-lg border border-gray-200 p-3 mt-2 bg-gray-50">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <i class="fas fa-table text-gray-400"></i>
                <span class="font-semibold text-gray-800 text-sm">${escapeHtml(t.table_name || 'derived')}</span>
                <span class="px-2 py-0.5 rounded text-xs font-mono" style="background:${color.bg};color:${color.text}">${escapeHtml(color.label)}</span>
            </div>
            <div class="text-xs text-gray-500">
                ${rows !== undefined ? `~${profilerFmtNum(rows)} rows` : ''}${filtered !== undefined ? ` · ${filtered}% filtered` : ''}
            </div>
        </div>
        <div class="grid grid-cols-2 gap-x-4 gap-y-1 mt-2 text-xs text-gray-600">
            <div><span class="text-gray-400">Key used:</span> ${key ? `<span class="font-mono text-blue-700">${escapeHtml(key)}</span>` : '<span class="text-red-600">none</span>'}</div>
            <div><span class="text-gray-400">Possible keys:</span> <span class="font-mono">${escapeHtml(String(possibleKeys))}</span></div>
        </div>
        ${extra.length ? `<div class="mt-2 flex flex-wrap gap-1">${extra.map(e => `<span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 text-xs">${escapeHtml(e)}</span>`).join('')}</div>` : ''}
    </div>`;
}

/* ---------------------------------------------------------------- */
/* Slow queries                                                      */
/* ---------------------------------------------------------------- */

function loadSlowQueries() {
    const container = document.getElementById('profiler-slow-results');
    container.innerHTML = '<div class="flex items-center justify-center py-12"><div class="loader"></div></div>';

    fetch('handlers/profiler_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'slow_queries', limit: 25 })
    })
    .then(res => res.json())
    .then(res => {
        container.dataset.loaded = '1';
        if (!res.success) {
            container.innerHTML = profilerErrorBox(res.message);
            return;
        }
        container.innerHTML = renderSlowQueries(res.data);
    })
    .catch(() => { container.innerHTML = profilerErrorBox('Request failed'); });
}

function renderSlowQueries(data) {
    const settings = data.settings || {};
    const banner = `<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4 text-xs text-gray-600 flex flex-wrap gap-4">
        <span><i class="fas fa-microchip text-gray-400 mr-1"></i> performance_schema: <strong>${escapeHtml(String(settings.performance_schema ?? 'unknown'))}</strong></span>
        <span><i class="fas fa-clock text-gray-400 mr-1"></i> long_query_time: <strong>${escapeHtml(String(settings.long_query_time ?? '—'))}s</strong></span>
        <span><i class="fas fa-file-alt text-gray-400 mr-1"></i> slow_query_log: <strong>${escapeHtml(String(settings.slow_query_log ?? '—'))}</strong></span>
    </div>`;

    if (!data.available) {
        return banner + `<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
            <i class="fas fa-info-circle mr-1"></i>
            performance_schema is not enabled on this server, so aggregated slow-query statistics aren't available.
            Enable it in MySQL (<code class="font-mono">performance_schema = ON</code>) to see this data, or use the profiler above to profile individual queries.
        </div>`;
    }

    if (!data.rows.length) {
        return banner + '<p class="text-sm text-gray-400 italic">No query statistics recorded yet.</p>';
    }

    let html = banner + '<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto"><table class="w-full text-sm">';
    html += `<thead><tr class="border-b border-gray-200 bg-gray-50">
        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Query</th>
        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">DB</th>
        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Executions</th>
        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Avg (ms)</th>
        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Max (ms)</th>
        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Rows Examined</th>
        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">No Index</th>
    </tr></thead><tbody class="divide-y divide-gray-100">`;

    data.rows.forEach(r => {
        const slow = Number(r.avg_time_ms) > 100;
        const noIndex = Number(r.no_index_used_count) > 0;
        html += `<tr class="hover:bg-gray-50">
            <td class="px-3 py-2 font-mono text-xs text-gray-700 max-w-md truncate" title="${escapeHtml(r.query_pattern || '')}">${escapeHtml((r.query_pattern || '').slice(0, 90))}</td>
            <td class="px-3 py-2 text-gray-600">${escapeHtml(r.db_name || '—')}</td>
            <td class="px-3 py-2 text-right text-gray-600">${profilerFmtNum(r.exec_count)}</td>
            <td class="px-3 py-2 text-right font-semibold ${slow ? 'text-red-600' : 'text-gray-800'}">${r.avg_time_ms}</td>
            <td class="px-3 py-2 text-right text-gray-600">${r.max_time_ms}</td>
            <td class="px-3 py-2 text-right text-gray-600">${profilerFmtNum(r.rows_examined)}</td>
            <td class="px-3 py-2 text-right">${noIndex ? '<span class="px-2 py-0.5 rounded bg-red-50 text-red-600 text-xs">yes</span>' : '<span class="text-gray-300">—</span>'}</td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    return html;
}

/* ---------------------------------------------------------------- */
/* History                                                            */
/* ---------------------------------------------------------------- */

function loadProfilerHistory() {
    const container = document.getElementById('profiler-history-results');
    container.innerHTML = '<div class="flex items-center justify-center py-12"><div class="loader"></div></div>';

    fetch('handlers/profiler_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'history', limit: 20 })
    })
    .then(res => res.json())
    .then(res => {
        container.dataset.loaded = '1';
        if (!res.success) {
            container.innerHTML = profilerErrorBox(res.message);
            return;
        }
        container.innerHTML = renderProfilerHistory(res.data);
    })
    .catch(() => { container.innerHTML = profilerErrorBox('Request failed'); });
}

function renderProfilerHistory(items) {
    if (!items.length) {
        return '<p class="text-sm text-gray-400 italic">No queries profiled yet. Run one from the "Profile a Query" tab.</p>';
    }

    return items.map(h => `
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-3 hover:border-indigo-300 transition cursor-pointer" onclick="reuseHistoryQuery(${escapeHtml(JSON.stringify(h.query))}, ${escapeHtml(JSON.stringify(h.database))})">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-gray-400">${escapeHtml(h.timestamp)} · <span class="font-semibold text-gray-500">${escapeHtml(h.database)}</span></span>
                <div class="flex items-center gap-3 text-xs">
                    <span class="text-indigo-600 font-semibold">${h.execution_time_ms} ms</span>
                    <span class="text-gray-500">${profilerFmtNum(h.rows_scanned)} scanned</span>
                    <span class="text-gray-500">${profilerFmtNum(h.rows_returned)} returned</span>
                    ${h.full_table_scans > 0 ? '<span class="text-red-600"><i class="fas fa-triangle-exclamation"></i> full scan</span>' : ''}
                </div>
            </div>
            <code class="text-xs font-mono text-gray-700 block truncate">${escapeHtml(h.query)}</code>
        </div>
    `).join('');
}

function reuseHistoryQuery(query, dbName) {
    showProfilerTab('run');
    document.querySelectorAll('.profiler-tab-btn').forEach(btn => {
        const active = btn.dataset.tab === 'run';
        btn.classList.toggle('bg-indigo-50', active);
        btn.classList.toggle('text-indigo-700', active);
        btn.classList.toggle('text-gray-600', !active);
    });
    document.getElementById('profiler-query').value = query;
    const select = document.getElementById('profiler-database');
    if (select && [...select.options].some(o => o.value === dbName)) {
        select.value = dbName;
        profilerDatabase = dbName;
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function clearProfilerHistory() {
    if (!confirm('Clear all profiled query history?')) return;

    fetch('handlers/profiler_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'clear_history' })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            showToast('History cleared');
            document.getElementById('profiler-history-results').dataset.loaded = '';
            loadProfilerHistory();
        } else {
            showToast(res.message, 'error');
        }
    });
}
