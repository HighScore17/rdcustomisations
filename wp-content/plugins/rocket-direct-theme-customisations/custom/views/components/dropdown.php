<?php

function renderDropdown( $id, $options, $value = "", $label = "", $class = "" ) {
  ?>
    <div class="relative inline-block text-left <?php echo $class; ?>">
      <div>
        <label><?php echo $label; ?></label>
        <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500" aria-expanded="true" aria-haspopup="true" id="<?php echo $id ?>-dropdown-button">
          Options
          <!-- Heroicon name: solid/chevron-down -->
          <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>

      <!--
        Dropdown menu, show/hide based on menu state.

        Entering: "transition ease-out duration-100"
          From: "transform opacity-0 scale-95"
          To: "transform opacity-100 scale-100"
        Leaving: "transition ease-in duration-75"
          From: "transform opacity-100 scale-100"
          To: "transform opacity-0 scale-95"
      -->
      <div class="hidden max-h-80 overflow-y-auto		 z-10 origin-top-left absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1" id="<?php echo $id ?>-dropdown-menu">
        <div class="py-1" role="none">
          <?php
            foreach( $options as $option ) : ?>
              <a href="#" class="text-gray-700 block px-4 py-2 text-sm" role="menuitem" tabindex="-1" id="menu-item-0" data-dval='<?php echo json_encode($option) ?>'> <?php echo $option["label"] ?> </a>
            <?php
            endforeach;
          ?>
          <!-- Active: "bg-gray-100 text-gray-900", Not Active: "text-gray-700" -->
          
        </div>
      </div>
    </div>
    <input type="hidden" id="<?php echo $id ?>-dropdown-value" name="<?php echo $id ?>-dropdown-value"/>
    
    <script>
      (($) => {
        const buttonId = '<?php echo $id ?>-dropdown-button';
        const menuId = '<?php echo $id ?>-dropdown-menu';
        const button = $(`#${buttonId}`);
        const menu = $(`#${menuId}`);
        let isOpen = false;
        let option = <?php echo $value ? "'".$value."'" : "null"  ?>;

        button.click(function() {
          if( isOpen ) closeDropdown();
          else openDropdown();
        });
        button.focusout(function() {
          setTimeout(closeDropdown ,200)
        });

        $(`#${menuId} a`).each(function() {
          $(this).click(function(e) {
            e.preventDefault();
            setValue( $(this).data('dval') )
          })
        })

        function setValue( val ) {
          if(!val) return;
          option = JSON.stringify(val);
          button.text(val.label);
          $('#<?php echo $id ?>-dropdown-value').val(option);
        }

        setValue( option ? JSON.parse(option) : null );
      
        function closeDropdown() {
          menu.addClass('hidden');
          isOpen = false;
        }

        function openDropdown() {
          menu.removeClass('hidden');
          isOpen = true;
        }
      })(jQuery);
    </script>
  <?php
}