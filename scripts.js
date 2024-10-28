/** Dashboard Script **/
function loadPage(url, pageTitle) {
    console.log("Loading page: " + url);

    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById('content-area').innerHTML = this.responseText;
            document.getElementById('current-page').innerText = pageTitle;

            // Call the function that initializes model dropdown after loading new content
            initializeModelDropdown();
        } else {
            console.log("Error loading page: " + this.status);
        }
    };

    const links = document.querySelectorAll('.nav-links a');
    links.forEach(link => link.classList.remove('active'));

    xhr.onerror = function() {
        console.log("Request error");
    };
    
    xhr.send();
}

function logout() {
    window.location.href = 'logout.php';
}

// Function to initialize model dropdown
function initializeModelDropdown() {
    const equipmentTypeSelect = document.getElementById('equipment_type');
    const modelNameSelect = document.getElementById('model_name');

    equipmentTypeSelect.addEventListener('change', function() {
        const equipTypeId = this.value;

        // Clear previous models
        modelNameSelect.innerHTML = '';

        if (equipTypeId) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'equipment_input_ict.php?equip_type_id=' + equipTypeId, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    const models = JSON.parse(this.responseText);
                    if (models.length > 0) {
                        models.forEach(function(model) {
                            const option = document.createElement('option');
                            option.value = model.model_id;
                            option.textContent = model.model_name;
                            modelNameSelect.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'No models available';
                        modelNameSelect.appendChild(option);
                    }
                } else {
                    alert('Error fetching models.');
                }
            };
            xhr.onerror = function() {
                alert('Request error');
            };
            xhr.send();
        }
    });
}

// Call this function on window load
window.onload = function() {
    initializeModelDropdown(); // Initialize when the window is loaded
};