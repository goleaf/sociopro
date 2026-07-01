
function fileValue(value) {
    var path = value.value;
    var extenstion = path.split('.').pop();
    if(extenstion == "jpg" || extenstion == "svg" || extenstion == "jpeg" || extenstion == "png"|| extenstion == "gif"){
        document.getElementById('image-preview').src = window.URL.createObjectURL(value.files[0]);
        var filename = path.replace(/^.*[\\\/]/, '').split('.').slice(0, -1).join('.');
        document.getElementById("filename").innerHTML = filename;
    }else{
        alert("File not supported. Kindly Upload the Image of below given extension ")
    }
}

// Select Option 
$('.fundraising-for-wrap .form-check .form-check-label').click(function(e) {
    $('.fundraising-for-wrap .form-check .form-check-label').removeClass('active');
    var $this = $(this);
    if (!$this.hasClass('active')) {
      $this.addClass('active');
    }
  });