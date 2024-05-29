<div>
  <label>Select users</label>
  <input type="file" id="users-csv" />
  <button class="button-primary" id="create-users">Create</button>
  <div class="loading-action" id="loading-actions" style="display: none">
    <img src="https://c.tenor.com/I6kN-6X7nhAAAAAi/loading-buffering.gif" width="200"/>
  </div>
</div>

  <table id="users-result">
    <thead>
      <tr>
        <th>Email</th>
        <th>Password</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
  <?php
    echo class_exists( 'Memcached' ) ? "Memcached exists" : "Memcached not exists";
  ?>


<script>

  (($) => {
    $(document).ready(function() {
      $('#create-users').click(function() {
        const files =  jQuery("#users-csv").prop('files');

        if( !files || !files.length ) {
          console.log(files);
          alert("Please select a valid file");
          return;
        }
        startLoading();
        const data = new FormData();
        data.append("action", "admin_create_users_by_batch");
        data.append("users", files[0]);
        $.ajax({
          type: "post",
          url: ajaxurl,
          data,
          cache: false,
          contentType: false,
          processData: false,
          success: function( result ) {
            console.log(result);
            if( result.data && Array.isArray(result.data) ) {
              renderUsers(result.data);
            }
            endLoading();
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
            console.error({ XMLHttpRequest, textStatus, errorThrown });
            alert("Failed to create users");
            endLoading();
          },
        })
      });

      function renderUsers( users ) {
        const html = users.map(user => `<tr><td>${user.email}</td><td>${user.password}</td></tr>`);
        $('#users-result tbody').html(html);
        let csvContent = "data:text/csv;charset=utf-8," + users.map(e => e.email + "," + e.password).join("\n");
        let encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "users.csv");
        document.body.appendChild(link);
        link.click();
      }
    
      function startLoading() {
        jQuery('#loading-actions').css("display", "flex");
      }

      function endLoading() {
        jQuery('#loading-actions').css("display", "none");
      }
    })
  })(jQuery)
</script>


<style>
  .loading-action {
    position: fixed;
    width: 100%;
    height: 100%;
    left: 0px;
    top: 0px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.5);
  }
  table, th, td {
  border: 1px solid black;
}
</style>