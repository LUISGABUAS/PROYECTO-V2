<?php
if (isset($_SESSION['mensaje'])):
?>
<script>
Swal.fire({
    icon: '<?= json_encode($_SESSION['icono']) ?>',
    title: 'Atención',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    showConfirmButton: false,
    timer: 2500
});
</script>
<?php
unset($_SESSION['mensaje'], $_SESSION['icono']);
endif;
?>
