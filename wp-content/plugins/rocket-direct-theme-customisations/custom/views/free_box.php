<p id="msg-status"></p>
<textarea style="width: 100%;" rows="5" id="orders-id"></textarea>

<button class="btn button-primary" id="send-emails">Send fre box email</button>

<br/><br/><br/><br/><br/><br/><br/>
<input type="text" placeholder="Range to CSV" id="ranges-values"/>
<button class="btn button-primary" id="range-to-csv">Convert</button>
<div id="ranges-parsed"></div>
<script>
  jQuery(document).ready(function($) {
    $('#send-emails').click(function() {
      $('#msg-status').html("Loading...");
      fetch('/wp-json/horizon/v1/free-box/email', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce' : wpApiSettings.nonce
        },
        body: JSON.stringify({
          orders: $('#orders-id').val()
        }),
        credentials: 'same-origin'
      }).then(function(response) {
        return response.json();
      }).then(function(data) {
        console.log(data);
        alert("Emails sent");
      })
      .catch(function(error) {
        console.log(error);
        $('#msg-status').html("error");
      })
      .finally(() => {
        $('#msg-status').html("");
      });
    });

    $('#range-to-csv').click(function() {
      const [ min, max ] = $('#ranges-values').val().split(',');
      console.log(min, max);
      const values = [];
      for( let i = parseInt(min); i <= parseInt(max); i++ ) {
        values.push(i);
      }
      $('#ranges-parsed').html(values.join(","))
    });
  });
</script>