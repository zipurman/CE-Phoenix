<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/
?>

  </div>

  <?php
  if (isset($_SESSION['admin'])) {
    require 'includes/footer.php';
  }

  echo $OSCOM_Hooks->call('siteWide', 'injectSiteEnd');
  ?>

  </div>
</div>

<?= $OSCOM_Hooks->call('siteWide', 'injectBodyEnd') ?>

</body>
</html>
