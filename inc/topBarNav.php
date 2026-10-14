<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top" id="topNavBar">
  <div class="container px-4 px-lg-5 ">
    <button class="navbar-toggler btn btn-sm" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span
        class="navbar-toggler-icon"></span></button>
    <a class="navbar-brand" href="./">
      <img src="<?php echo validate_image($_settings->info('logo')) ?>" width="30" height="30"
        class="d-inline-block align-top" alt="" loading="lazy">
      <?php echo $_settings->info('short_name') ?>
    </a>

    <!-- <form class="form-inline" id="search-form">
                  <div class="input-group">
                    <input class="form-control form-control-sm form " type="search" placeholder="Search" aria-label="Search" name="search"  value="<?php echo isset($_GET['search']) ? $_GET['search'] : "" ?>"  aria-describedby="button-addon2">
                    <div class="input-group-append">
                      <button class="btn btn-outline-success btn-sm m-0" type="submit" id="button-addon2"><i class="fa fa-search"></i></button>
                    </div>
                  </div>
                </form> -->
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
        <li class="nav-item"><a class="nav-link <?= isset($page) && $page == 'home' ? "active" : '' ?>"
            aria-current="page" href="./">Inicio</a></li>
        <li class="nav-item"><a class="nav-link <?= isset($page) && $page == 'products' ? "active" : '' ?>"
            href="./?p=products">Productos</a></li>
        <li class="nav-item"><a class="nav-link <?= isset($page) && $page == 'services' ? "active" : '' ?>"
            href="./?p=services">Servicios</a></li>
        <li class="nav-item"><a class="nav-link <?= isset($page) && $page == 'brands' ? "active" : '' ?>"
            href="./?p=brands">Marcas</a></li>
        <li class="nav-item"><a class="nav-link <?= isset($page) && $page == 'about' ? "active" : '' ?>"
            href="./?p=about">Nosotros</a></li>
      </ul>
      <div class="d-flex align-items-center">
        <div>
          <?php if ($_settings->userdata('id') > 0 && $_settings->userdata('login_type') == 2): ?>
            <div class="d-flex align-items-center">
              <div class="navbar-nav nav-item mr-3">
                <?php
                $cart_count = $conn->query("SELECT SUM(quantity) from cart_list where client_id = '{$_settings->userdata('id')}'")->fetch_array()[0];
                $cart_count = $cart_count > 0 ? number_format($cart_count) : 0;
                ?>
                <a href="./?p=cart" class="nav-link position-relative">
                  <i class="fas fa-shopping-cart fa-lg"></i>
                  <span id="cart_count" class="badge badge-danger rounded-circle position-absolute"
                    style="top: -5px; right: -10px;"><?= $cart_count ?></span>
                  <span class="d-none d-md-inline ml-1">Carrito</span>
                </a>
              </div>
              <div class="navbar-nav nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button"
                  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-user"></i> <span class="d-none d-md-inline">Hola,
                    <?= $_settings->userdata('firstname') ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                  <a class="dropdown-item" href="./?p=my_orders">Mis Pedidos</a>
                  <a class="dropdown-item" href="./?p=my_services">Mis Servicios</a>
                  <a class="dropdown-item" href="./?p=manage_account">Mi Cuenta</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="./classes/Login.php?f=logout_client">Cerrar Sesión</a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <a href="./login.php" class="text-light text-decoration-none mx-2"><b>Acceder</b></a>
            <a href="./register.php" class="text-light text-decoration-none mx-2"><b>Registro</b></a>
            <a href="./admin/login.php" class="text-light text-decoration-none mx-2 d-none d-md-inline"><b>Admin</b></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</nav>
<script>
  $(function () {
    $('#login-btn').click(function () {
      uni_modal("", "login.php")
    })
    $('#navbarResponsive').on('show.bs.collapse', function () {
      $('#mainNav').addClass('navbar-shrink')
    })
    $('#navbarResponsive').on('hidden.bs.collapse', function () {
      if ($('body').offset.top == 0)
        $('#mainNav').removeClass('navbar-shrink')
    })
  })

  $('#search-form').submit(function (e) {
    e.preventDefault()
    var sTxt = $('[name="search"]').val()
    if (sTxt != '')
      location.href = './?p=products&search=' + sTxt;
  })
</script>