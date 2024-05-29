<aside class="side-menu">
  <ul>
    <?php
      foreach( $tabs as $slug => $content ) {
        ?>
          <li>
            <a href="?tab=<?php echo $slug ?>&page=<?php echo $_GET["page"] ?>"> 
          <?php echo $content["title"] ?></li>
          </a> 
        <?php
      }
    ?>
    
  </ul>
</aside>