/* 
 * PeanutCMD backend javascript
 */




$(function() {
  $.maxZIndex = $.fn.maxZIndex = function(opt) {
    /// <summary>
    /// Returns the max zOrder in the document (no parameter)
    /// Sets max zOrder by passing a non-zero number
    /// which gets added to the highest zOrder.
    /// </summary>
    /// <param name="opt" type="object">
    /// inc: increment value,
    /// group: selector for zIndex elements to find max for
    /// </param>
    /// <returns type="jQuery" />
    var def = { inc: 10, group: "*" };
    $.extend(def, opt);
    var zmax = 0;
    $(def.group).each(function() {
      var cur = parseInt($(this).css('z-index'));
      zmax = cur > zmax ? cur : zmax;
    });
    if (!this.jquery)
      return zmax;

    return this.each(function() {
      zmax += def.inc;
      $(this).css("z-index", zmax);
    });
  }

  $(".button").button();
/*
  $('textarea.wysiwyg').tinymce({
    script_url : "js/tinymce/tiny_mce.js",

    // General options
    theme : "advanced",
    skin : "cirkuit",
    plugins : "autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,emotions,iespell,jqueryinlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

    // Theme options
//    theme_advanced_buttons1 : "save,cancel,|,spellchecker,preview,|,cut,copy,paste,pastetext,pasteword,|,undo,redo,|,search,replace,|,table,link,unlink,image,|,cleanup,code,help,fullscreen",
    theme_advanced_buttons1 : "formatselect,|,bold,italic,underline,strikethrough,|,,justifyleft,justifycenter,justifyright,|,numlist,bullist,|,link,unlink,pagebreak,charmap,|,undo,redo,|,code,fullscreen,help",
    theme_advanced_buttons2 : "",
    theme_advanced_buttons3 : "",
//    theme_advanced_buttons2 : "formatselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,numlist,bullist,outdent,indent",
//    theme_advanced_buttons3 : "examplesbutton,blockquote,|,sub,sup,|,removeformat,|,insertdate,inserttime,|,charmap,emotions,media,|,restoredraft",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resize_horizontal : false,
    theme_advanced_resizing : true,

    // Example content CSS (should be your site CSS)
    content_css : "js/tinymce/css/content.css",

    // Drop lists for link/image/media/template dialogs
    template_external_list_url : "lists/template_list.js",
    external_link_list_url : "lists/link_list.js",
    external_image_list_url : "lists/image_list.js",
    media_external_list_url : "lists/media_list.js",

    // Replace values for the template plugin
    template_replace_values : {
      username : "Some User",
      staffid : "991234"
    }
  });
*/
  $(document).bind('keydown', 'esc', function () {
    $(".menubar").find(".items").hide();
  });

  var over = false;
  var inputFocus = false;
  
  $(":text, textarea").bind("blur focus", function(event) {
    inputFocus = event.type == "focus";
  });
  
  $(document).bind('click', function () {
    if (!over)
      $(".menubar").find(".items").hide();
  });
  
  $(".item a").each(function() {
    var shortcut = $(this).data("shortcut");
    var label = $(this).html();
    var link = $(this);
    $(this).prepend('<div class="key">' + shortcut + '</div>');
    if (!$(this).parent().hasClass('header')) {
      var items = $(this).parent().parent();
      var keystate = false;
      $(document).bind('keydown keyup', shortcut, function(event) {
        if (keystate && event.type == 'keydown')
          return;
        if (!keystate && event.type == 'keyup')
          return;
        keystate = event.type == 'keydown';
        if (!keystate)
          return;
        if (items.css('display') != "none") {
//          console.log("Pressed " + shortcut + ": " + label);
          window.location.href = link.attr("href");
        }
      });
    }
  });
  

  $(".menu").each(function() {
    var items = $(this).find(".items");
    var key = $(this).find(".header a").data("shortcut");
    $(this).bind('mouseover mouseout', function (event) {
      if (event.type == 'mouseover') {
        over = true;
        items.parent().parent().find(".items").hide();
        items.show().maxZIndex();
      } else {
        over = false;
        items.hide();
      }
    });
//    console.log("Binding " + key + "...");
    var keystate = false;
    $(document).bind('keydown keyup', key, function (event) {
      if (keystate && event.type == 'keydown')
        return;
      if (!keystate && event.type == 'keyup')
        return;
      keystate = event.type == 'keydown';
      if (!keystate)
        return;
//      console.log("Pressed " + key);
      if (inputFocus)
        return;
      if (items.css('display') == "none") {
        items.parent().parent().find(".items").hide();
        items.show().maxZIndex();
        items.find('.item:first').find('a').focus();
      }
      else {
        items.parent().parent().find(".items").hide();
      }
    });
  });
  
  $(".radioset, .buttonset").buttonset();
  
  $("#check_settings").bind("change", function() {
    if (this.checked)
      $("#settings").show("blind", 200);
    else
      $("#settings").hide("blind", 200);
  });
  
  $(".permalink").each(function(index, Element) {
    var input = $(this);
    var titleId = input.data('title-id');
    var allowSlash = input.hasClass('permalink-allow-slash');
    var posted = false;
    var title = null;
    if (titleId) {
      title = $("#" + titleId)
      title.keyup(function() {
        var title = $(this).val();
        if (allowSlash)
          title = title.replace(/[^(a-zA-Z0-9 \-\/)]/g, "").replace(/[ \-]/g, "-").toLowerCase();
        else
          title = title.replace(/[^(a-zA-Z0-9 \-)]/g, "").replace(/[ \-]/g, "-").toLowerCase();
        input.val(title);
      });
    }
    input.bind("blur focus", function(event) {
      if (event.type == "focus")
        input.parent().addClass("permalink-wrapper-focus");
      else
        input.parent().removeClass("permalink-wrapper-focus");
    });
    input.parent().click(function() {
      input.focus();
    });
  });
  
});
