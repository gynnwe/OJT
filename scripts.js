/** Dashboard Script **/
function loadPage(url, pageTitle) {
    console.log("Loading page: " + url);

    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById('content-area').innerHTML = this.responseText;
            document.getElementById('current-page').innerText = pageTitle;
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

    if (!equipmentTypeSelect || !modelNameSelect) return;

    // Remove any existing event listeners by cloning
    const newEquipmentTypeSelect = equipmentTypeSelect.cloneNode(true);
    equipmentTypeSelect.parentNode.replaceChild(newEquipmentTypeSelect, equipmentTypeSelect);

    newEquipmentTypeSelect.addEventListener('change', function() {
        const equipTypeId = this.value;

        // Clear all existing options
        while (modelNameSelect.firstChild) {
            modelNameSelect.removeChild(modelNameSelect.firstChild);
        }

        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Brand/Model Name';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        modelNameSelect.appendChild(defaultOption);

        if (equipTypeId) {
            fetch(`equipment_input_ict.php?equip_type_id=${equipTypeId}`)
                .then(response => response.json())
                .then(data => {
                    // Use a Map to ensure unique models
                    const uniqueModels = new Map();
                    data.forEach(model => {
                        if (!uniqueModels.has(model.model_id)) {
                            uniqueModels.set(model.model_id, model);
                        }
                    });

                    // Add unique models to select
                    uniqueModels.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.model_id;
                        option.textContent = model.model_name;
                        modelNameSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching models:', error);
                    const errorOption = document.createElement('option');
                    errorOption.value = '';
                    errorOption.textContent = 'Error loading models';
                    errorOption.disabled = true;
                    modelNameSelect.appendChild(errorOption);
                });
        }
    });
}

// Initialize when the window is loaded
document.addEventListener('DOMContentLoaded', initializeModelDropdown);