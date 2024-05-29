<?php

class Woocommerce_Integration_Components {
  static function input( $args = array() ) {
    $defaultArgs = array(
      "class" => "appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline bg-gray-600"
    );
    $args["class"] = isset($args["class"]) ? ($args["class"] . " " . $defaultArgs["class"]) : $defaultArgs["class"];
    $args["type"] = $args["type"] ?? "text";
    $attrs_str = self::argsToString( $args );
    $label = self::getLabel( $args );

    echo " $label <input $attrs_str/>";
  }

  static function checkbox( $args = array() ) {
    $args["type"] = "checkbox";

    if( isset( $args["checked"] ) && !$args["checked"] ) {
      unset($args["checked"]);
    }

    $attrs_str = self::argsToString( $args );
    $label = self::getLabel( $args );
    echo "<input $attrs_str/> $label";
  }

  static function button( $args = array() ) {
    $defaultArgs = array(
      "class" => "transition ease-in-out  bg-green-500 py-2 px-4 rounded-md text-white hover:bg-green-700	cursor-pointer"
    );
    $args["class"] = isset($args["class"]) ? ($args["class"] . " " . $defaultArgs["class"]) : $defaultArgs["class"];
    $attrs_str = self::argsToString( $args );
    echo " <button $attrs_str>" . $args["content"] . "</button>";
  }

  static function argsToString( $args ) {
    $attrs = [];
    foreach( $args as $key => $value ) {
      if( $value ) {
        $attrs[] = $key . "=\"" . $value . "\"";
      } else {
        $attrs[] = $key;
      }
    }

    return implode(" ", $attrs);
  }

  static function getLabel( $args ) {
    return isset( $args["label"] ) ? "<label for=\"" . $args["id"] ."\">".$args["label"]."</label>" : "";
  }
}