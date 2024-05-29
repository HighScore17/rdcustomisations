var valid_payment = false;
var creditcard = new CreditCard();
var successCallback = function () {
  var checkout_form = $("form.woocommerce-checkout");

  // deactivate the tokenRequest function event
  checkout_form.off("checkout_place_order", tokenRequest);

  // submit the form now
  checkout_form.submit();
};

var errorCallback = function (data) {
  console.log(data);
};

var tokenRequest = function () {
  if(window.payment_method && window.payment_method === "usio")
  {
    if (!valid_payment) return false;
    var checkout_form = $("form.woocommerce-checkout");
    var name = checkout_form.find("#billing_first_name").val();
    var lastname = checkout_form.find("#billing_last_name").val();
    var email = checkout_form.find("#billing_email").val();
    var card = checkout_form.find("#usio_ccNo").val().toString();
    card = card.split(" ").join(""); //remove white spaces
    var expdate = checkout_form.find("#usio_expdate").val().toString();
    expdate = expdate.split(" ").join(""); //remove white spaces
    var cvv = checkout_form.find("#usio_cvv").val().toString();
    var url =
      "https://checkout.securepds.com/checkout/checkout.svc/JSON/GenerateToken";

    var data = {
      MerchantKey: usio_params.api_key,
      PaymentType: "cc",
      EmailAddress: email,
      CardNumber: card,
      ExpDate: expdate,
      CVV: cvv,
      BankRouting: "",
      BankAccountNumber: "",
      BankAccountType: "",
    };

    var settings = {
      url:
        "https://checkout.securepds.com/checkout/checkout.svc/JSON/GenerateToken",
      method: "POST",
      timeout: 0,
      headers: {
        "Content-Type": "application/json",
      },
      data: JSON.stringify(data),
    };
    $.ajax(settings).done(function (response) {
      console.log(response);
      if (response.Status === "success") {
        $("#card_token").val(response.Confirmation);
        successCallback();
        return true;
      } else {
        // show error data
        errorCallback(response);
        return false;
      }
    });
  }
  else if(window.payment_method && window.payment_method === "paypal")
    return true;
  return false;
};

function cc_format(value) {
  var v = value.replace(/\s+/g, "").replace(/[^0-9]/gi, "");
  var matches = v.match(/\d{4,16}/g);
  var match = (matches && matches[0]) || "";
  var parts = [];  
  for (i = 0, len = match.length; i < len; i += 4) {
    parts.push(match.substring(i, i + 4));
  }
  if (parts.length) {
    return parts.join(" ");
  } else {
    return value;
  }
}

function cardFormat(input) {
  var number = input.value;
  var autotoggle = input.dataset.auto;
  var format_number = cc_format(number);
  input.value = format_number;
  //send to next input
  if (number.length >= 19 && (!autotoggle || autotoggle === "false")) {
    var valid = creditcard.isValid(number);
    if (valid) {
      input.dataset.auto = "true";
      $("#usio_expdate").focus();
      $("#alert_usio_ccNo").removeClass("active");
    } else {
      $("#alert_usio_ccNo").addClass("active");
    }
  } else if (number.length < 19 && autotoggle === "true") {
    input.dataset.auto = "false";
  }
}

function dateFormat(input) {
  var text = input.value;
  var autotoggle = input.dataset.auto;
  if (text.length === 2) {
    text += " / ";
  }
  //add slash on copy&paste
  if (text.length === 4 && text.search("/") < 0) {
    text = text[0] + text[1] + " / " + text[0] + text[1];
  }
  input.value = text;
  //send to next input
  if (text.length >= 7 && (!autotoggle || autotoggle === "false")) {
    var valid = validateDate(text);
    if (valid) {
      input.dataset.auto = "true";
      valid_payment = true;
      $("#usio_cvv").focus();
      $("#alert_usio_expdate").removeClass("active");
    } else {
      /// date not valid
      $("#alert_usio_expdate").addClass("active");
    }
  } else if (text.length < 7 && autotoggle === "true") {
    input.dataset.auto = "false";
  }
}

function validateDate(date) {
  var mm_yy = date.split("/").map(function (item) {
    return parseInt(item);
  });

  var current_date = new Date();
  var month = current_date.getMonth();
  var year = current_date.getFullYear();
  if (2000 + mm_yy[1] > year) {
    return true;
  }
  return mm_yy[0] >= month + 1 && 2000 + mm_yy[1] >= year;
}
function validate_cvv(cvv){

  var myRe = /^[0-9]{3,4}$/;
  var myArray = myRe.exec(cvv);
  //delete spaces and caracteres
  usiocvv = $('#usio_cvv');
  usiocvv.keyup(function() {
    $(this).val($(this).val().replace(/\s/g, '').replace(/\D/g, ''));
  });

  if(cvv!=myArray)
  {
    return false;
  }else{
    return true;  //valid cvv number
  }
}

function validateForm() {
  var usio_ccNo = creditcard.isValid($("#usio_ccNo").val());
  var usio_expdate = validateDate($("#usio_expdate").val());
  var usio_cvv = validate_cvv($("#usio_cvv").val());
  if (!usio_ccNo || !usio_expdate || !usio_cvv) {
    valid_payment = false;
    return false;
  }
  valid_payment = true;
  $("#place_order").removeClass("disabled");
  $("#place_order").attr("disabled", false);
}

jQuery(function ($) {
  var checkout_form = $("form.woocommerce-checkout");
  checkout_form.on("checkout_place_order", tokenRequest);
});
