<header>
  <div class="container" style="display:flex;align-items:center;justify-content:space-between;">
    <h1 style="margin:0;font-size:1.05rem;">Student</h1>
    <nav>
      <a href="notifications.php" style="margin-right:12px;">Notifications</a>
      <a href="edit_user.php?edit_id=<?= (int)$user_id ?>" style="margin-right:12px;">Edit</a>

      <!-- replaced anchor-wrapped button with a POST form to ensure logout reliably clears session -->
      <form method="POST" action="logout.php" style="display:inline-block;margin:0;padding:0;">
        <button type="submit" class="btn ghost" style="margin:0;">Logout</button>
      </form>
    </nav>
  </div>
</header>