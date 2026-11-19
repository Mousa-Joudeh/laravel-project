document.addEventListener('DOMContentLoaded', () => {
    const source = document.getElementById('dash-activity-data');
    const chart = document.getElementById('dash-performance-chart');
    const labels = document.getElementById('dash-chart-labels');
    const empty = document.getElementById('dash-chart-empty');
    const title = document.getElementById('dash-chart-series-label');
    const total = document.getElementById('dash-chart-series-total');
    const tabs = [...document.querySelectorAll('[data-dash-metric]')];

    if (!source || !chart || !labels || !empty || !title || !total) return;

    let data;

    try {
        data = JSON.parse(source.textContent);
    } catch {
        return;
    }

    const svgNS = 'http://www.w3.org/2000/svg';
    const format = (value, decimals = 0) => Number(value).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });

    function element(tag, attrs = {}) {
        const node = document.createElementNS(svgNS, tag);

        Object.entries(attrs).forEach(([name, value]) => {
            node.setAttribute(name, String(value));
        });

        return node;
    }

    function draw(metric) {
        const values = data.map(item => Number(item[metric]) || 0);
        const minValue = Math.min(0, ...values);
        const maxRange = Math.max(0, ...values);
        const magnitude = Math.max(maxRange - minValue, 1);
        const left = 43;
        const top = 12;
        const width = 680;
        const height = 209;
        const bottom = top + height;
        const x = index => left + index / Math.max(values.length - 1, 1) * width;
        const y = value => top + (maxRange - value) / magnitude * height;
        const points = values.map((value, index) => [x(index), y(value)]);
        const stroke = metric === 'payments' ? '#0a9c79' : '#23769f';
        const decimals = metric === 'payments' ? 2 : 0;

        chart.replaceChildren();
        labels.replaceChildren();

        const defs = element('defs');
        const gradient = element('linearGradient', { id: 'dash-chart-gradient', x1: '0', y1: '0', x2: '0', y2: '1' });
        gradient.append(element('stop', { offset: '0%', 'stop-color': stroke, 'stop-opacity': '.18' }));
        gradient.append(element('stop', { offset: '100%', 'stop-color': stroke, 'stop-opacity': '0' }));
        defs.append(gradient);
        chart.append(defs);

        for (let i = 0; i <= 4; i++) {
            const value = maxRange - i * magnitude / 4;
            const yy = top + height * i / 4;
            chart.append(element('line', { x1: left, y1: yy, x2: left + width, y2: yy, stroke: '#e8eef1', 'stroke-dasharray': '3 5' }));

            const text = element('text', { x: left - 9, y: yy + 4, 'text-anchor': 'end', fill: '#9aa5b0', 'font-size': '11' });
            text.textContent = Number.isInteger(value) ? format(value) : format(value, 1);
            chart.append(text);
        }

        if (points.length) {
            const path = points.map(([px, py], index) => `${index === 0 ? 'M' : 'L'} ${px.toFixed(2)} ${py.toFixed(2)}`).join(' ');
            const area = `${path} L ${x(points.length - 1)} ${bottom} L ${x(0)} ${bottom} Z`;
            chart.append(element('path', { d: area, fill: 'url(#dash-chart-gradient)' }));
            chart.append(element('path', { d: path, fill: 'none', stroke, 'stroke-width': '3', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));

            points.forEach(([px, py], index) => {
                const circle = element('circle', { cx: px, cy: py, r: values.length <= 14 ? 3.8 : 2, fill: stroke, stroke: '#fff', 'stroke-width': '1.5' });
                const tooltip = element('title');
                tooltip.textContent = `${data[index].label}: ${format(values[index], decimals)} ${metric === 'payments' ? 'JOD' : 'bookings'}`;
                circle.append(tooltip);
                chart.append(circle);
            });
        }

        const labelCount = Math.min(7, data.length);
        const selectedIndexes = Array.from({ length: labelCount }, (_, index) => Math.round(index * (data.length - 1) / Math.max(labelCount - 1, 1)));

        selectedIndexes.forEach(index => {
            const span = document.createElement('span');
            span.textContent = data[index]?.label || '';
            labels.append(span);
        });

        const sum = values.reduce((result, value) => result + value, 0);
        total.textContent = `${format(sum, decimals)} ${metric === 'payments' ? 'JOD' : ''}`.trim();
        title.textContent = metric === 'payments' ? 'Net payments · JOD' : 'Bookings created';
        empty.hidden = values.some(value => value !== 0);
        chart.setAttribute('aria-label', metric === 'payments' ? 'Net processed payments over time in JOD' : 'Booking counts over time');
        tabs.forEach(tab => {
            const active = tab.dataset.dashMetric === metric;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-pressed', String(active));
        });
    }

    if (!Array.isArray(data) || !data.length) return;
    tabs.forEach(tab => tab.addEventListener('click', () => draw(tab.dataset.dashMetric)));
    draw('payments');
});
