<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
?>

<div class="content-wrapper">
<section class="content">
<div class="container-fluid">

<h3 class="mb-4"><i class="fa fa-chart-bar"></i> Reporte de clientes que más compran</h3>

<div class="card">
    <div class="card-body">
        <svg id="chart" width="100%" height="500"></svg>
    </div>
</div>

</div>
</section>
</div>

<!-- D3 -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<script>
fetch('<?= $URL ?>/app/controllers/clientes/reporte_clientes.php')
.then(res => res.json())
.then(resp => {

    if (!resp.success) {
        alert('No se pudo cargar el reporte');
        return;
    }

    const data = resp.data;

    const svg = d3.select('#chart');
    const width  = svg.node().getBoundingClientRect().width;
    const height = 500;

    svg.attr('viewBox', `0 0 ${width} ${height}`);

    const margin = { top: 40, right: 30, bottom: 80, left: 80 };
    const w = width  - margin.left - margin.right;
    const h = height - margin.top  - margin.bottom;

    const g = svg.append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    /* =========================
       ESCALAS
    ========================= */
    const x = d3.scaleBand()
        .domain(data.map(d => d.nombre_completo))
        .range([0, w])
        .padding(0.25);

    const y = d3.scaleLinear()
        .domain([0, d3.max(data, d => +d.total_compras)])
        .nice()
        .range([h, 0]);

    /* =========================
       EJES
    ========================= */
    g.append('g')
        .attr('transform', `translate(0,${h})`)
        .call(d3.axisBottom(x))
        .selectAll('text')
        .attr('transform', 'rotate(-35)')
        .style('text-anchor', 'end');

    g.append('g')
        .call(d3.axisLeft(y));

    /* =========================
       TOOLTIP
    ========================= */
    const tooltip = d3.select('body')
        .append('div')
        .style('position','absolute')
        .style('background','#1f1f1f')
        .style('color','#fff')
        .style('padding','8px 12px')
        .style('border-radius','6px')
        .style('font-size','13px')
        .style('pointer-events','none')
        .style('opacity',0);

    /* =========================
       BARRAS
    ========================= */
    g.selectAll('.bar')
        .data(data)
        .enter()
        .append('rect')
        .attr('x', d => x(d.nombre_completo))
        .attr('y', h)
        .attr('width', x.bandwidth())
        .attr('height', 0)
        .attr('rx', 4)
        .attr('fill', d =>
            d.tipo_cliente === 'local'
                ? '#28a745'   // verde
                : '#007bff'   // azul
        )
        .on('mouseover', (e,d) => {
            tooltip
                .style('opacity',1)
                .html(`
                    <strong>${d.nombre_completo}</strong><br>
                    Tipo: ${d.tipo_cliente}<br>
                    Compras realizadas: ${d.total_compras}
                `);
        })
        .on('mousemove', e => {
            tooltip
                .style('left', (e.pageX + 10) + 'px')
                .style('top',  (e.pageY - 25) + 'px');
        })
        .on('mouseout', () => tooltip.style('opacity',0))
        .transition()
        .duration(900)
        .attr('y', d => y(d.total_compras))
        .attr('height', d => h - y(d.total_compras));

});
</script>

<?php include('../layout/mensajes.php'); ?>
<?php include('../layout/parte2.php'); ?>
