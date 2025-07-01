jQuery(document).ready(function ($) {
  // Clear cache button
  $("#llms-txt-generator-clear-cache").on("click", function () {
    var $button = $(this);
    $button.prop("disabled", true);

    $.ajax({
      url: wpLLMsTxt.ajaxUrl,
      type: "POST",
      data: {
        action: "llms_txt_generator_clear_cache",
        nonce: wpLLMsTxt.nonce,
      },
      success: function (response) {
        if (response.success) {
          alert("Cache cleared successfully!");
        } else {
          alert("Failed to clear cache. Please try again.");
        }
      },
      error: function () {
        alert("Failed to clear cache. Please try again.");
      },
      complete: function () {
        $button.prop("disabled", false);
      },
    });
  });

  // Regenerate file button
  $("#llms-txt-generator-regenerate").on("click", function () {
    var $button = $(this);
    $button.prop("disabled", true);

    $.ajax({
      url: wpLLMsTxt.ajaxUrl,
      type: "POST",
      data: {
        action: "llms_txt_generator_regenerate_file",
        nonce: wpLLMsTxt.nonce,
      },
      success: function (response) {
        if (response.success) {
          alert("File regenerated successfully!");
          window.location.reload();
        } else {
          alert("Failed to regenerate file. Please try again.");
        }
      },
      error: function () {
        alert("Failed to regenerate file. Please try again.");
      },
      complete: function () {
        $button.prop("disabled", false);
      },
    });
  });

  // Form submission
  $("form").on("submit", function () {
    return true;
  });
});
