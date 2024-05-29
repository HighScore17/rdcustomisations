<div>
  <input id="search_product_id"/>
  <button id="search_product_id_btn">Search</button>  
</div>
<div>
  <select id="select_meta_key">
  </select><input id="select_meta_key_custom"/>
  <textarea id="update_product_id" style="width: 100%" rows="6"></textarea>
  
  <button id="update_product_id_btn">update</button>  
</div>
<div id="status_wp">

</div>

<script>
var wp_metadata_options = [];
jQuery('#search_product_id_btn').click(function(){
  jQuery.ajax({
      type: "post",
      url: ajaxurl,
      data: {
        action: "get_manage_metadata",
        id: jQuery('#search_product_id').val()
      },
      success: function(result){
        result = JSON.parse(result);
        console.log(result);
        wp_metadata_options = result;
          result.forEach(function(metadata){
            jQuery('#select_meta_key').append(new Option(metadata.key, metadata.key));
          })
      },
      error: function(XMLHttpRequest, textStatus, errorThrown)
      {
        console.error(XMLHttpRequest, textStatus, errorThrown);
      }
  });
});

jQuery('#update_product_id_btn').click(function(){
  jQuery.ajax({
      type: "post",
      url: ajaxurl,
      data: {
        action: "set_manage_metadata",
        id: jQuery('#search_product_id').val(),
        key: jQuery('#select_meta_key_custom').val() || jQuery('#select_meta_key').val(),
        value: jQuery('#update_product_id').val()
      },
      beforeSend: function()
      {
        jQuery('#status_wp').text('Loading');
      },
      success: function(result){
        console.log(result);
        jQuery('#status_wp').text('Success');
      },
      error: function(XMLHttpRequest, textStatus, errorThrown)
      {
        console.error(XMLHttpRequest, textStatus, errorThrown);
        jQuery('#status_wp').text('Error');
      }
  });
});

jQuery('#select_meta_key').change(function(){
  jQuery('#update_product_id').val(
    wp_metadata_options.find((val) => val.key === jQuery(this).val()).value
  )
})
</script>