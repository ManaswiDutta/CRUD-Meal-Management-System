<?php

if (isset($_SESSION['role_id'])) {

  if ($_SESSION['role_id'] == 3){
    include "includes/admin_header.php";
  }

  elseif ($_SESSION['role_id'] == 2){
    include "includes/super_header.php";
  }

  elseif ($_SESSION['role_id'] == 1){
    include "includes/student_header.php";
  }
}

// else{
//   echo "not logged in";
// }

?>