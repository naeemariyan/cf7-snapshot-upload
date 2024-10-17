var cameraContainer;
cameraContainer = document.querySelector('.webcame-container');
if(cameraContainer){
    let cw = cameraContainer.offsetWidth;
    let ch = cameraContainer.offsetHeight;
    cw = (cw) ? cw : 600;
    ch = (ch) ? ch : 400;
    Webcam.set({
        width: cw,
        height: ch,
        image_format: 'jpeg',
        jpeg_quality: 100
    });

    document.querySelectorAll('[data-web-camera]').forEach(function(cam) {
       Webcam.attach(cam);
    });
}

document.addEventListener('DOMContentLoaded', function() {

  var submitButton, form_id, form, wrapper_id, capture_id, submitButtons, wrapper_id;
  submitButtons = document.querySelectorAll('.wpcf7-submit');

  submitButtons.forEach(function(submitButton){
     submitButton = document.querySelector('.wpcf7-submit');
      form_id = submitButton.closest('[data-wpcf7-id]').getAttribute('data-wpcf7-id');
      form = document.querySelector('[data-wpcf7-id="'+form_id+'"]');
      capture_id = form.querySelector('[data-capture-container]').getAttribute('data-capture-container');

      if (capture_id) {
        submitButton.style.display = 'none';
        var newButton = document.createElement('button');
        newButton.textContent = 'Upload & Submit'; 
        newButton.className = 'wpcf7-form-control wpcf7-submit has-spinner';  
        newButton.setAttribute('type', 'button');  
        newButton.setAttribute("onclick", "UploadImages('#"+capture_id+"', '#field-"+capture_id+"', '"+form_id+"')");  
        submitButton.parentNode.insertBefore(newButton, submitButton);
      }
  });
});


function insert_captured_image(data_uri, capture_id){
    let imageContainer = document.createElement('div');
    imageContainer.className = 'captured-img-box';
    let imgElement = document.createElement('img');
    imgElement.className = 'captured-img';
    let button = document.createElement('button');
    button.innerHTML = 'x';
    button.className = 'removeImage';
    button.setAttribute('onclick', 'TrashImage(this.parentNode)');
    imgElement.src = data_uri;
    imageContainer.appendChild(imgElement);
    imageContainer.appendChild(button);
    document.getElementById(capture_id).appendChild(imageContainer);
}

function captureCameraImge($this, capture_id){

    Webcam.snap(function(data_uri) {
        insert_captured_image(data_uri, capture_id);
    });

}

function FieldsValidation(container){
    console.log(container.querySelectorAll('[aria-required="true"]'));
    return Array.from(container.querySelectorAll('[aria-required="true"]')).filter(input => input.value == '' || input.value == null);
}

function UploadImages(container_id, capture_id, form_id){
    var capturedImages, capture_field, container, submitButton, url, response;
    capturedImages = getImagesObject(container_id);
    capture_field = document.querySelector(capture_id);
    container = document.querySelector('[data-wpcf7-id="'+form_id+'"]');
    submitButton = container.querySelector('input.wpcf7-submit');
    url = capture_field.getAttribute('data-action');
    validate = FieldsValidation(container);

    if(validate.length > 0){
        submitButton.click();
        return;
    }
    if (capturedImages.length === 0) {
        capture_field.value = '';
        submitButton.click();
        return;
    }

    if(url){
        url = url + '/wp-admin/admin-ajax.php';
    }else{
        console.log("End point is missing");
        return false;
    }

    let formData = new FormData();
    formData.append('action', 'cf7_camera_handle_images');
    let images = [];
    capturedImages.forEach((image, index) => {
        let blob = dataURItoBlob(image);
        formData.append(`image_${index}`, blob, `image_${index}.png`);
    });

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    }).then(response => response.json())
    .then(result => {
        if (result.success) {
            capture_field.value = (result.data.uploaded_images.length > 0) ? result.data.uploaded_images.join(',') : '';
            submitButton.click();
        } else {
            alert('Failed to upload images.');
        }
    }).catch(error => {
        console.log(error);
        alert('An error occurred while uploading images.');
    });

}



function dataURItoBlob(dataURI) {
    let byteString = atob(dataURI.split(',')[1]);
    let mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
    let arrayBuffer = new ArrayBuffer(byteString.length);
    let uint8Array = new Uint8Array(arrayBuffer);
    for (let i = 0; i < byteString.length; i++) {
        uint8Array[i] = byteString.charCodeAt(i);
    }
    return new Blob([uint8Array], {type: mimeString});
}



function TrashImage(container){
    container.remove();
}

function getImagesObject(id){
    let capturedImages = [];
    document.querySelector(id).querySelectorAll('.captured-img').forEach(function(image){
        capturedImages.push(image.getAttribute('src'));
    });
    return capturedImages;
}

function media_uploadtype(get, del, container_id) {
   var wrapper = document.querySelector('.media-uploa-wrapper');
   var container_get = wrapper.querySelector(get);
   var container_del = wrapper.querySelector(del);
   if(container_get){
    container_get.style.display = 'block';
   }
   if(container_del){
    container_del.style.display = 'none';
   }
   if(container_id){
    document.getElementById(container_id).innerHTML = '';
   }
}

// Function to handle image preview and append to the container
function update_media_container(event, container_id) {
    console.log(container_id);
    const files = event.target.files; 
    const container = document.getElementById(container_id);
    container.innerHTML = '';
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                insert_captured_image(e.target.result, container_id);
            };
            reader.readAsDataURL(file);
        }
    });
}

document.addEventListener( 'wpcf7mailsent', function( event ) {
    var form_id, container, field_id, files, url, urls;
    form_id = event.detail.contactFormId;
    container = document.querySelector('[data-wpcf7-id="'+form_id+'"]');
    field_id = container.querySelector('[data-capture-container]').getAttribute('data-capture-container');
    
    if(field_id){
        files = container.querySelector('#field-'+field_id);
        urls = files.value;
        url = files.getAttribute('data-action');

        if(url){
            url = url + '/wp-admin/admin-ajax.php';
            let formData = new FormData();
            formData.append('action', 'cf7_camera_handle_remove_images');
            formData.append('urls', urls);
            document.getElementById(field_id).innerHTML = '';

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            }).then(response => response.json())
            .then(result => {
                console.log(result);
            }).catch(error => {
                console.log(error);
                alert('An error occurred while uploading images.');
            });

        }else{
            console.log("End point is missing");
            return false;
        }
    }

}, false );