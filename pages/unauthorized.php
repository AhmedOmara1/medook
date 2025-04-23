<?php
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center" data-aos="fade-up">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle text-warning fa-5x"></i>
            </div>
            <h1 class="mb-4">Access Denied</h1>
            <p class="lead mb-4">Sorry, you don't have permission to access this page.</p>
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title">Why am I seeing this?</h5>
                    <p class="card-text">You're trying to access a page that requires different permissions than what your account has. This could be because:</p>
                    <ul class="text-start">
                        <li>You're trying to access an admin area without admin privileges</li>
                        <li>You're trying to access a doctor's dashboard without being registered as a doctor</li>
                        <li>You're attempting to access a resource that belongs to another user</li>
                    </ul>
                </div>
            </div>
            <div class="d-grid gap-2 d-md-block">
                <a href="home.php" class="btn btn-primary px-4 py-2 me-md-2">Go to Homepage</a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary px-4 py-2">Go Back</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 