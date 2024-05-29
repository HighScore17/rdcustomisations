<?php
  $products_ids = get_posts(
    array(
      'posts_per_page' => -1,
      'post_type' => array('product'),
      'fields' => 'ids',
      'post_status' => array('publish', 'private')
    )
  );
  $b2b_groups = get_posts( array( 'post_type' => 'b2bking_group','post_status'=>'publish','numberposts' => -1) );
?>

<div id="header">
  <div>
    <h1 style="padding: 0px;">Prices</h1>
  </div>
  <div>
    <input type="text" id="search_product" placeholder="Search"/>
  </div>
  <div>
    <div class="import-container">
      <button onclick="open_file('csv-prices')" class="btn button btn-primary button-primary" id="import-btn-text"> Select Prices </button>
      <input onchange="changeFile()" type="file" id="csv-prices" style="display: none;"/>
      <button disabled onclick="import_prices_csv()"  class="btn button btn-primary button-primary" id="import-btn"> Import </button>
    </div>
  </div>
  <div>
    <p id="import-status" style="margin: 0px;"></p>
  </div>
</div>

<!-- Import to groups Checkbox -->
<div class="import-groups">
  <span>
    <input id="import-group-b2c" type="checkbox" data-import="groups" data-group="b2c" />
    <label for="import-group-b2c" >B2C</label>
  </span>
  <?php
    foreach( $b2b_groups as $group ) {
      echo "
      <span>
        <input id=\"import-group-$group->ID\" type=\"checkbox\" data-import=\"groups\" data-group=\"$group->ID\" />
        <label for=\"import-group-$group->ID\" >$group->post_title</label>
      </span>
      ";
    }
  ?>
</div>

<div id="products-container">
</div>

<script>
const products = [
  <?php 
    foreach( $products_ids as $product_id ) {
      $product = wc_get_product( $product_id );
      $name = $product->get_name();
      $image = $product->get_image();
      echo "
      {
        id: $product_id,
        name: '$name',
        image: '$image',
      },
      ";
    } 
  ?>
]
const groups = [
  {name: 'B2C', id: 'b2c'},
  <?php 
    foreach( $b2b_groups as $group ) {
      $name = $group->post_title;
      $id = $group->ID;
      echo "{ name: '$name', id: $id },";
    }

  ?>
]
jQuery(document).ready(() => {
  renderProducts("");

  jQuery("#search_product").keyup(function(e) {
    renderProducts(e.target.value);
  });

  function renderProducts( toSearch ) {
    jQuery('#products-container').html("");
    for(let product of products) {
      const id = product.id;
      if(product.name.toLowerCase().includes(toSearch.toLowerCase() || ""))
      {
        const options = groups?.map(group => `<option value="${group.id}">${group.name}</option>`);
        jQuery('#products-container').append(`
          <div class="product-container">
            <div>
              ${product.image}
              <p style="margin-left: 10px">${product.name}</p>
            </div>
            <div>
              <select id="group-${id}" style="margin-right: 10px; max-width: 200px">${options}</select>
              <button onclick="download_prices_csv(${id}, false)" class="btn button btn-primary button-primary"> Download Prices </button>
              <button onclick="download_prices_csv(${id}, true)" class="btn button btn-primary button-primary" style="margin-left: 10px"> Download Prices (Advanced version) </button>
            </div>
          </div>
        `
        )
      }
    }
  }
});

function open_file( fileid )
{
  jQuery(`#${fileid}`).click();
}
function download_prices_csv(id, advanced)
{
  const groupID = jQuery('#group-' + id).val();
  jQuery.ajax({
    type: "post",
    url: ajaxurl,
    data: {
      action: "print_prices_csv",
      id,
      advanced,
      group: jQuery('#group-' + id).val()
    },
    success: function(result){
        console.log(result);
        const uri = encodeURI("data:text/csv;charset=utf-8," + result);
        const link = document.createElement("a");
        link.setAttribute("href", uri);
        link.setAttribute("download", (products.find(p => p.id === id)?.name || "prices") + ".csv");
        document.body.appendChild(link);
        link.click();
        link.remove();
    },
    error: function(XMLHttpRequest, textStatus, errorThrown)
    {
      console.error(XMLHttpRequest, textStatus, errorThrown);
    }
});
}
function changeFile(id) {
  if(jQuery("#csv-prices").prop('files')[0])
  {
    jQuery('#import-btn').attr("disabled", false);
    jQuery('#import-btn-text').text(jQuery("#csv-prices").prop('files')[0].name);
  }
}
/*jQuery("#csv-prices").change(function(){
  if(jQuery("#csv-prices").prop('files')[0])
  {
    jQuery('#import-btn').attr("disabled", false);
    jQuery('#import-btn-text').text(jQuery("#csv-prices").prop('files')[0].name);
  }
})
*/
function import_prices_csv()
{
  jQuery('#import-status').text("Importing...");
  const groups = [];
  jQuery('input[data-import="groups"]').each(function() {
    if( jQuery(this).is(":checked") )
      groups.push(jQuery(this).data("group"));
  });

  const data = new FormData();
  data.append("prices", jQuery("#csv-prices").prop('files')[0]);
  data.append("action", 'csv_import_prices');
  data.append("ids", groups.join())
  jQuery.ajax({
    type: "post",
    url: ajaxurl,
    data,
    cache: false,
    contentType: false,
    processData: false,
    success: function(result){
        console.log(result);
        if(result.success)
          jQuery('#import-status').text("Import Completed");
        else
          jQuery('#import-status').text("Failed to import");
    },
    error: function(XMLHttpRequest, textStatus, errorThrown)
    {
      console.error(XMLHttpRequest, textStatus, errorThrown);
      jQuery('#import-status').text("Failed to import");
    }
});
}
</script>



<style>
  #header {
    display: flex;
    margin: 10px 0px;
  }
  #header > div:not(:last-child) {
    margin-right: 20px;
  }
  #products-container img {
    width: 50px;
    height: 50px;
  }
  .product-container {
    display: flex; 
    margin-bottom: 20px;
    background-color: #ffffff;
    padding: 10px 20px;
    border-radius: 3px;
  }
  .product-container > div {
    width: 70%;
    display: flex;
    align-items:flex-start;
    align-content:flex-start;
    align-items: center;
  }
  .product-container > div:first-child .attachment-50 {
    border-radius: 3px;
  }
  .product-container > div:first-child{
    width: 30%;
  }
  .product-container > div > .import-container{
    background-color: #ccc;
    padding: 5px 10px;
    border-radius: 3px;
    margin-left: 10px;
  }
  #import-btn-text{
    margin-right: 5px;
  }
  .import-groups {
    background-color: #f2f2f2;
    margin-bottom: 30px;
  }
  .import-groups > span {
    display: inline-block;
    margin-right: 20px;
  }
</style>