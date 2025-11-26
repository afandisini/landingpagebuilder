<form method="post" action="?r=login">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
    <div class="mb-3 position-relative">
        <i class="bi bi-envelope input-icon"></i>
        <label for="email" class="form-label">Email</label>
        <input
            type="email"
            name="email"
            id="email"
            class="form-control has-icon"
            placeholder="Masukan Email Anda"
            required
        >
    </div>
    <div class="mb-3 position-relative">
        <i class="bi bi-person-lock input-icon"></i>
        <label for="password" class="form-label">Password</label>
        <input
            type="password"
            name="password"
            id="password"
            class="form-control has-icon"
            placeholder="Katasandi Anda"
            required
        >
        <i class="bi bi-eye-slash password-toggle"></i>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
    </button>
</form>
