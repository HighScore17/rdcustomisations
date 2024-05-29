function download_prices_csv(id)
{
  jQuery.ajax({
    type: "post",
    url: ajaxurl,
    data: {
      action: "print_prices_csv",
      id
    },
    success: function(result){
        console.log(result)
    },
    error: function(XMLHttpRequest, textStatus, errorThrown)
    {
      console.error(XMLHttpRequest, textStatus, errorThrown);
    }
});
}