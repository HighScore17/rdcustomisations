<?php
echo '<div class="wrap">';
echo '<h1>Horizon Customisations</h1>';
echo '<p>This is a custom page for the Horizon theme.</p>';
echo '</div>';

global $wpdb;
$table_name = Active_Campaign_Free_Sample::get_table_name();
$deals = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

?>
<div id="ac-response-error">
</div>
<div id="ac-response-success">
</div>
<div class="deals-container">
<?php
foreach($deals as $deal):
  ?>
  <div class="deals-item" action="/wp-admin/admin.php?page=free_samples" method="post">
    <p><strong>Email: </strong><?php echo $deal["email"] ?></p>
    <p><strong>Name: </strong><?php echo $deal["firstname"] . " " . $deal["lastname"] ?></p>
    <p><strong>Deal Value: </strong><?php echo "$" . number_format($deal["value_raw"], 2)   ?></p>
    <label>Tracking Number: </label>
    <br/>
    <input placeholder="Tracking number" class="input" type="text" id="deal-tracking-<?php echo $deal['deal_id'] ?>" value="<?php echo $deal['tracking_number'] ?>"/>
    <br/>
    <label>Stage</label>
    <br/>
    <select id="deal-stage-for-<?php echo $deal['deal_id'] ?>">
      <option 
        value="<?php echo Active_Campaign_Free_Sample::AC_HOLD_STAGE ?>" 
        <?php echo $deal["stage_id"] == Active_Campaign_Free_Sample::AC_HOLD_STAGE ?  "selected" : "" ?>>
        On Hold
      </option>
      <option 
        value="<?php echo Active_Campaign_Free_Sample::AC_SHIPPED_STAGE ?>"
        <?php echo $deal["stage_id"] == Active_Campaign_Free_Sample::AC_SHIPPED_STAGE ?  "selected" : "" ?>>
        Shipped
      </option>
      <option 
        value="<?php echo Active_Campaign_Free_Sample::AC_DELIVERED_STAGE ?>"
        <?php echo $deal["stage_id"] == Active_Campaign_Free_Sample::AC_DELIVERED_STAGE ?  "selected" : "" ?>>
        Delivered
      </option>
    </select>
    <input type="submit" value="Update" class="btn button" onclick="updateDealStage(<?php echo $deal['deal_id'] ?>)">
  </div>
  <?php
endforeach;
?>
</div>

<script>
  clearDealMessages();
  function updateDealStage( dealId ) {
    const stageId = document.getElementById("deal-stage-for-" + dealId).value;
    const trackingNumber = document.getElementById("deal-tracking-" + dealId).value;
    console.log("Updating deal stage for deal id: " + dealId + " to stage id: " + stageId);
    clearDealMessages();
    fetch("/wp-json/horizon/v1/free-sample/deal", {
      method: "PUT",
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        deal_id: dealId,
        stage_id: stageId,
        tracking_number: trackingNumber
      })
    })
    .then(response => response.json())
    .then(data => {
      console.log(data);
      if(data.err)
      showErrorMessage(data.err);
      else if (data.response)
      showSuccessMessage("Deal updated successfully");
    })
    .catch(error => showErrorMessage("Can't update deal stage"));
  }

  function clearDealMessages() {
    showSuccessMessage("");
    showErrorMessage("");
  }

  function showSuccessMessage(message) {
    showMessage("ac-response-success", message);
  }

  function showErrorMessage(message) {
    showMessage("ac-response-error", message);
  }

  function showMessage( id, message ) {
    $message_container = document.getElementById(id);
    $message_container.innerHTML = message;
    $message_container.style.display = message !== "" ? "block" : "none";
  }
</script>

<style>
  #wpcontent {
    padding-right: 20px;
  }
  .deals-container {
    display: flex;
    flex-wrap: wrap;
  }

  .deals-item {
    background-color: #ccc;
    margin: 0px 10px 10px 10px;
    padding: 10px;
  }
  #ac-response-error {
    background-color: red;
    color: #fff;   
    padding: 10px 20px;
    border-radius: 3px;
    margin-bottom: 20px;
  }
  #ac-response-success {
    background-color: green;
    color: #fff;   
    padding: 10px 20px;
    border-radius: 3px;
    margin-bottom: 20px;
  }
  input[type="text"] {
    margin-bottom: 10px;
    margin-top: 5px;
  }
  p {
    margin-top: 0px;
    margin-bottom: 5px;
  }
</style>