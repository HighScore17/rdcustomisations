<?php 
    wp_register_script('main_script', plugin_dir_url(__FILE__). "../js/main.js", array(), '1.0.0');
    wp_enqueue_script('main_script');
    //wp_register_style('bootstrap', plugin_dir_url(__FILE__) . "../css/bootstrap.css", array(), '4.5.2');
    //wp_register_style('main', plugin_dir_url(__FILE__) . "../css/main.css", array(), '1.0.0');
    $users = get_users(); 
?>

<form id="form_add" onsubmit="multiple_address_add(event)">
    <label>User</label>
    <br/>
    <select name="user_id">
    <?php
        foreach($users as $user) :
            ?>
                <option value="<?= $user->data->ID ?>"> <?= $user->data->display_name ?> </option>
            <?php
        endforeach
    ?>
    </select>
    <br/>
    <label>Type</label>
    <br/>
    <select name="type">
        <option value="shipping">Shipping</option>
        <option value="billing">Billing</option>
    </select>
    <br/>
    <label>Alias</label>
    <br/>
    <input type="text" name="alias" required/>
    <br/>
    <label>First Name</label>
    <br/>
    <input type="text" name="firstName" required/>
    <br/>
    <label for="lastName">Last Name</label>
    <br/>
    <input type="text" name="lastName" id="lastName" required/>
    <br/>
    <label for="lastName">Company</label>
    <br/>
    <input type="text" name="company" id="company"/>
    <br/>
    <label for="lastName">Country</label>
    <br/>
    <input type="text" name="country" id="country" required/>
    <br/>
    <label for="lastName">Address</label>
    <br/>
    <input type="text" name="address" id="address" required/>
    <br/>
    <label for="lastName">Apartment</label>
    <br/>
    <input type="text" name="apartment"/>
    <br/>
    <label for="lastName">City</label>
    <br/>
    <input type="text" name="city" required/>
    <br/>
    <label for="lastName">State</label>
    <br/>
    <input type="text" name="state" required/>
    <br/>
    <label for="lastName">Post Code</label>
    <br/>
    <input type="text" name="postcode" required/>
    <br/>
    <label for="lastName">Phone</label>
    <br/>
    <input type="text" name="phone" required/>
    <br/>
    <label for="lastName">email</label>
    <br/>
    <input type="text" name="email" required/>
    <br/>
    <br/>
    <input type="submit" value="Add"/>

</form>