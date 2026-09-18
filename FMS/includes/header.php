<?php
$pageTitle = $pageTitle ?? 'LinkFlow';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — LinkFlow</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/FMS/assets/css/style.css">
    <script src="/FMS/assets/js/tinymce_8.9.1/tinymce/js/tinymce/tinymce.min.js"></script>
    
    <script>
  document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
      selector: '#interest_statement, #reason_for_application, #expected_learning_objectives',
      height: 300,
      license_key: 'gpl',               
      plugins: 'lists link image table code wordcount',
      toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image table | code',
      menubar: false,
      branding: false                   
    });
  });
document.addEventListener('DOMContentLoaded', function() {
    
    flatpickr("#requested_start_date", {
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
        minDate: "today"
    });

    flatpickr("#requested_end_date", {
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
        enableTime: true,
        minDate: "today"
    });
    flatpickr("#dob", {
        altInput: true,
        altFormat: "F j, Y",
        dateFormat: "Y-m-d",
        maxDate: "2009-12-31", // Prevents selecting dates after 2009
        defaultDate: "2009-01-01" // Opens calendar initially at 2009
    });


});
document.addEventListener('click', function (e) {
    // ...your existing click-handling code...

    const toggleBtn = e.target.closest('.toggle-password');
    if (toggleBtn) {
        const input = document.querySelector(toggleBtn.dataset.target);
        const icon  = toggleBtn.querySelector('i');
        const showing = input.type === 'text';

        input.type = showing ? 'password' : 'text';
        icon.classList.toggle('fa-eye', showing);
        icon.classList.toggle('fa-eye-slash', !showing);
    }
});
</script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

<?php
// Display flash messages (success or error) if any are set
$flash = get_flash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>
         alert-dismissible fade show m-2" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

