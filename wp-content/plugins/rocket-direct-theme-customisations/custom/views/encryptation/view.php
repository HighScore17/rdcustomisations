<?php
require __DIR__ . "/controller.php";
?>

<script src="https://cdn.tailwindcss.com"></script>


<?php 
  if(!$system_key) {
    ?>
      <form class="border border-slate-400 rounded-md p-3 mt-3" method="post">
      <p class="text-lg	mb-3 font-bold">Generate system key</p>
      <input type="hidden" name="caction" value="generate-system-key"/>
      <input type="submit" class=" transition ease-in-out  bg-green-500 py-2 px-4 rounded-md text-white hover:bg-green-700	cursor-pointer	" value="Generate"/>
</form>
    <?php
  }
?>


<form class="border border-slate-400 rounded-md p-3 mt-3" method="post">
<p class="text-lg	mb-3 font-bold">User Keys</p>
    <?php if( $cmessage["to"] === "create-users-keys" ): ?>
      <div class="bg-white rounded p-1 mb-3 inline-block	">
      <p> <?php echo $cmessage["content"] ?> </p>
    </div>
    <?php endif; ?>
    <br/>
  <input type="hidden" name="caction" value="create-users-keys"/>
  <input type="submit" class=" transition ease-in-out  bg-green-500 py-2 px-4 rounded-md text-white hover:bg-green-700	cursor-pointer	" value="Create Encryptation Keys for Users"/>
</form>
<form class="border border-slate-400 rounded-md p-3 mt-3" method="post">
<p class="text-lg	mb-3 font-bold">Encrypt Credit Cards</p>
    <?php if( $cmessage["to"] === "encrypt-cc-tokens" ): ?>
      <div class="bg-white rounded p-1 mb-3 inline-block	">
      <p> <?php echo $cmessage["content"] ?> </p>
    </div>
    <?php endif; ?>
    <br/>
  <input type="hidden" name="caction" value="encrypt-cc-tokens"/>
  <input type="submit" class=" transition ease-in-out  bg-green-500 py-2 px-4 rounded-md text-white hover:bg-green-700	cursor-pointer	" value="Encrypt Credit Cards"/>
</form>

<form class="border border-slate-400 rounded-md p-3 mt-3" method="post">
<p class="text-lg	mb-3 font-bold">Encrypt/Decrypt System Data</p>
    <?php if( $cmessage["to"] === "encrypt-cc-tokens" ): ?>
      <div class="bg-white rounded p-1 mb-3 inline-block	">
      <p> <?php echo $cmessage["content"] ?> </p>
    </div>
    <?php endif; ?>
    <br/>
  <textarea type="text" placeholder="Value to Encrypt" name="value-to-encrypt" class="shadow appearance-none border  rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" rows="4" value=""><?php echo $cmessage["encrypted"] ?? "" ?></textarea>
  <textarea type="text" placeholder="Value to Decrypt" name="value-to-decrypt" class="shadow appearance-none border  rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" rows="4" value=""><?php echo $cmessage["decrypted"] ?? ""?></textarea>
  <input type="hidden" name="caction" value="encrypt-decrypt-system"/>
  <input type="submit" class=" transition ease-in-out  bg-green-500 py-2 px-4 rounded-md text-white hover:bg-green-700	cursor-pointer	" value="Encrypt / Decrypt"/>
</form>