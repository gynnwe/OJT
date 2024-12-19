<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

// Create connection
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function getEquipmentTypes($conn)
{
    $query = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getYears($conn)
{
    $currentYear = date('Y');
    $years = array();

    // Generates next 5 years including current year
    for ($i = 0; $i < 5; $i++) {
        $year = $currentYear + $i;
        $years[] = array('year_maintained' => $year);
    }

    return $years;
}

// Fetch Maintenance Plan and Details
function fetchMaintenancePlan($conn, $planId)
{
    $query = "SELECT * FROM maintenance_plan WHERE id = :planId";
    $stmt = $conn->prepare($query);
    $stmt->execute([':planId' => $planId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchPlanDetails($conn, $planId)
{
    $query = "SELECT month, target, equip_type_id, details, accomplishment 
                  FROM plan_details 
                  WHERE maintenance_plan_id = :planId";
    $stmt = $conn->prepare($query);
    $stmt->execute([':planId' => $planId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalServiceableEquipment($conn, $equipmentTypeId)
{
    $query = "SELECT COUNT(*) as total_serviceable FROM equipment WHERE status = 'Serviceable' AND equip_type_id = :equipmentTypeId";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':equipmentTypeId', $equipmentTypeId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total_serviceable'];
}

$equipmentTypes = getEquipmentTypes($conn);
$years = getYears($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Equipment Maintenance</h1>

        <!-- Trigger Button for Modal -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
            Open Maintenance Form
        </button>

        <?php
        // Fetch all maintenance plans
        $queryPlans = "SELECT * FROM maintenance_plan ORDER BY date_prepared DESC";
        $stmtPlans = $conn->prepare($queryPlans);
        $stmtPlans->execute();
        $maintenancePlans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);

        $planDetails = [];
        foreach ($maintenancePlans as $plan) {
            // Fetch plan details grouped by equipment type and month
            $stmt = $conn->prepare("
        SELECT 
            pd.*,
            et.equip_type_name
        FROM 
            plan_details pd
        JOIN 
            equipment_type et ON pd.equip_type_id = et.equip_type_id
        WHERE 
            pd.maintenance_plan_id = :plan_id
        ORDER BY 
            pd.equip_type_id, 
            FIELD(pd.month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')
    ");
            $stmt->execute(['plan_id' => $plan['id']]);
            $rawDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group details by equipment type
            $processedDetails = [];
            $currentEquipType = null;

            foreach ($rawDetails as $detail) {
                if ($currentEquipType !== $detail['equip_type_id']) {
                    // Start a new equipment type group
                    $currentEquipType = $detail['equip_type_id'];
                    $processedDetails[$currentEquipType] = [
                        'equip_type_id' => $detail['equip_type_id'],
                        'equip_type_name' => $detail['equip_type_name'],
                        'months' => []
                    ];
                }

                // Add month details to the current equipment type
                $processedDetails[$currentEquipType]['months'][$detail['month']] = [
                    'target' => $detail['target'],
                    'details' => $detail['details'],
                    'accomplishment' => $detail['accomplishment']
                ];
            }

            // Convert to indexed array to maintain compatibility with existing code
            $planDetails[$plan['id']] = array_values($processedDetails);
        }

        if ($maintenancePlans):
        ?>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Plan</th>
                        <th>Year</th>
                        <th>Date Prepared</th>
                        <th>Total Count</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenancePlans as $plan): ?>
                        <tr>
        <td>Maintenance Plan <?= htmlspecialchars($plan['id']) ?></td>
        <td><?= htmlspecialchars($plan['year']) ?></td>
        <td><?= htmlspecialchars($plan['date_prepared']) ?></td>
        <td><?= htmlspecialchars($plan['count']) ?></td>
        <td><?= htmlspecialchars($plan['status']) ?></td>
        <td>
            <a href="maintenance_plan_view.php?plan_id=<?= $plan['id'] ?>" class="btn btn-info btn-sm">View Plan</a>
            
            <?php if ($plan['status'] === 'pending'): ?>
                <button type="button"
                    class="btn btn-warning btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#editModal<?= $plan['id'] ?>">
                    Edit
                </button>
                <button type="button"
                    class="btn btn-success btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#submitModal<?= $plan['id'] ?>">
                    Submit
                </button>
            <?php endif; ?>

            <?php if ($plan['status'] !== 'trash' && $plan['status'] !== 'submitted' && $plan['status'] !== 'archive'): ?>
                <button type="button"
                    class="btn btn-danger btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#trashModal<?= $plan['id'] ?>">
                    Trash
                </button>
            <?php endif; ?>

            <?php if ($plan['status'] === 'trash'): ?>
                <button type="button"
                    class="btn btn-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#recoverModal<?= $plan['id'] ?>">
                    Recover
                </button>
            <?php endif; ?>
        </td>
    </tr>

                        <!-- Modal -->
                        <div class="modal fade" id="submitModal<?= $plan['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $plan['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalLabel<?= $plan['id'] ?>">Confirm Submission</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Do you want to submit this maintenance plan?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                                        <form method="POST" action="submit_maintenance_plan.php" style="display:inline;">
                                            <input type="hidden" name="plan_id" value="<?= htmlspecialchars($plan['id']) ?>">
                                            <button type="submit" class="btn btn-success">Yes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
    <!-- Trash Modal -->
    <div class="modal fade" id="trashModal<?= $plan['id'] ?>" tabindex="-1" aria-labelledby="trashModalLabel<?= $plan['id'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="trashModalLabel<?= $plan['id'] ?>">Move to Trash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to move Maintenance Plan <?= htmlspecialchars($plan['id']) ?> to trash?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="update_maintenance_status.php" method="POST" style="display: inline;">
                        <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                        <input type="hidden" name="status" value="trash">
                        <button type="submit" class="btn btn-danger">Move to Trash</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recover Modal -->
    <div class="modal fade" id="recoverModal<?= $plan['id'] ?>" tabindex="-1" aria-labelledby="recoverModalLabel<?= $plan['id'] ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recoverModalLabel<?= $plan['id'] ?>">Recover Maintenance Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to recover Maintenance Plan <?= htmlspecialchars($plan['id']) ?>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="update_maintenance_status.php" method="POST" style="display: inline;">
                        <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-primary">Recover</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
                        <div class="modal fade" id="editModal<?= $plan['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $plan['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="post" action="edit_plan_maintenance_process.php" id="editPlanForm<?= $plan['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?= $plan['id'] ?>">Edit Maintenance Plan <?= htmlspecialchars($plan['id']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body" id="equipmentContainer<?= $plan['id'] ?>">
                                            <!-- Year Dropdown -->
                                            <div class="mb-3">
                                                <label for="year_maintained<?= $plan['id'] ?>" class="form-label">Select Year:</label>
                                                <select name="year_maintained" id="year_maintained<?= $plan['id'] ?>" class="form-select" required>
                                                    <option value="">--Select Year--</option>
                                                    <?php foreach ($years as $year): ?>
                                                        <option value="<?= htmlspecialchars($year['year_maintained']) ?>"
                                                            <?= $year['year_maintained'] == $plan['year'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($year['year_maintained']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Equipment Types Container -->
                                            <?php foreach ($planDetails[$plan['id']] as $detail): ?>
                                                <div class="equipment-entry border p-3 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h6>Equipment Type: <?= htmlspecialchars($detail['equip_type_name']) ?></h6>
                                                        <!-- <button type="button" class="btn btn-danger btn-sm remove-equipment">Remove</button> -->
                                                    </div>
                                                    <div class="mb-3">
                                                        <select name="equipment_types[]" class="form-select" required hidden>
                                                            <option value="">--Select Equipment Type--</option>
                                                            <?php foreach ($equipmentTypes as $type): ?>
                                                                <option value="<?= htmlspecialchars($type['equip_type_id']) ?>"
                                                                    <?= $type['equip_type_id'] == $detail['equip_type_id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($type['equip_type_name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <!-- Monthly counts for this equipment -->
                                                    <div class="row">
                                                        <?php
                                                        $months = [
                                                            'January',
                                                            'February',
                                                            'March',
                                                            'April',
                                                            'May',
                                                            'June',
                                                            'July',
                                                            'August',
                                                            'September',
                                                            'October',
                                                            'November',
                                                            'December'
                                                        ];
                                                        foreach ($months as $month):
                                                        ?>
                                                            <div class="col-md-3 mb-3">
                                                                <label class="form-label"><?= $month ?>:</label>
                                                                <input type="number"
                                                                    name="counts[<?= $detail['equip_type_id'] ?>][<?= $month ?>]"
                                                                    class="form-control"
                                                                    min="0"
                                                                    step="0.01"
                                                                    value="<?= htmlspecialchars($detail['months'][$month]['target'] ?? 0) ?>"
                                                                    required>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Add More Equipment Button -->
                                        <!-- <div class="modal-body">
                                            <button type="button" class="btn btn-success" id="addMoreEquipment<?= $plan['id'] ?>">
                                                Add Another Equipment Type
                                            </button>
                                        </div> -->
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No maintenance plans available.</p>
        <?php endif; ?>

        <!-- Modal -->
        <div class="modal fade" id="maintenanceModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="post" action="add_plan_maintenance_process.php">
                        <div class="modal-header">
                            <h5 class="modal-title">Equipment Maintenance Form</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Year Dropdown -->
                            <div class="mb-3">
                                <label for="year_maintained" class="form-label">Select Year:</label>
                                <select name="year_maintained" id="year_maintained" class="form-select" required>
                                    <option value="">--Select Year--</option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?= htmlspecialchars($year['year_maintained']) ?>">
                                            <?= htmlspecialchars($year['year_maintained']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Equipment Types Container -->
                            <div id="equipmentTypesContainer">
                                <div class="equipment-entry border p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6>Equipment Entry</h6>
                                        <button type="button" class="btn btn-danger btn-sm remove-equipment" style="display: none;">Remove</button>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Select Equipment Type:</label>
                                        <select name="equipment_types[]" class="form-select" required>
                                            <option value="">--Select Equipment Type--</option>
                                            <?php foreach ($equipmentTypes as $type): ?>
                                                <option value="<?= htmlspecialchars($type['equip_type_id']) ?>">
                                                    <?= htmlspecialchars($type['equip_type_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Monthly counts for this equipment -->
                                    <div class="row">
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label"><?= date("F", mktime(0, 0, 0, $i, 1)) ?>:</label>
                                                <input type="number" name="counts[0][<?= $i ?>]" class="form-control" min="0" required>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Add More Equipment Button -->
                            <button type="button" class="btn btn-success" id="addMoreEquipment">
                                Add Another Equipment Type
                            </button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let equipmentCount = 0;
    const container = document.getElementById('equipmentTypesContainer');
    const addButton = document.getElementById('addMoreEquipment');

    // Function to get all currently selected equipment types
    function getSelectedEquipmentTypes() {
        const selectedTypes = new Set();
        container.querySelectorAll('select[name="equipment_types[]"]').forEach(select => {
            if (select.value) {
                selectedTypes.add(select.value);
            }
        });
        return selectedTypes;
    }

    // Function to update disabled states on all selects
    function updateEquipmentSelects() {
        const selectedTypes = getSelectedEquipmentTypes();
        const allSelects = container.querySelectorAll('select[name="equipment_types[]"]');
        
        allSelects.forEach(select => {
            const currentValue = select.value;
            select.querySelectorAll('option').forEach(option => {
                if (option.value && option.value !== currentValue) {
                    option.disabled = selectedTypes.has(option.value);
                }
            });
        });
    }

    // Add change event listeners to initial select
    container.querySelector('select[name="equipment_types[]"]').addEventListener('change', updateEquipmentSelects);

    // Modified add button click handler
    addButton.addEventListener('click', function() {
        equipmentCount++;
        const template = container.querySelector('.equipment-entry').cloneNode(true);

        // Update name attributes for the new equipment entry
        template.querySelectorAll('input[name^="counts[0]"]').forEach(input => {
            const month = input.name.match(/\[(\d+)\]$/)[1];
            input.name = `counts[${equipmentCount}][${month}]`;
            input.value = ''; // Clear the value
        });

        // Reset and update the new select
        const newSelect = template.querySelector('select[name="equipment_types[]"]');
        newSelect.selectedIndex = 0;
        
        // Add change event listener to the new select
        newSelect.addEventListener('change', updateEquipmentSelects);

        // Show remove button for additional entries
        template.querySelector('.remove-equipment').style.display = 'block';

        container.appendChild(template);
        
        // Update disabled states after adding new entry
        updateEquipmentSelects();
    });

    // Modified remove button handler
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-equipment')) {
            e.target.closest('.equipment-entry').remove();
            // Update disabled states after removing an entry
            updateEquipmentSelects();
        }
    });

    // Form submission validation
    const form = container.closest('form');
    form.addEventListener('submit', function(event) {
        const equipmentSelects = container.querySelectorAll('select[name="equipment_types[]"]');
        const selectedTypes = new Set();
        let hasEmptySelection = false;

        equipmentSelects.forEach(select => {
            if (!select.value) {
                hasEmptySelection = true;
            } else if (selectedTypes.has(select.value)) {
                event.preventDefault();
                alert('Duplicate equipment types are not allowed.');
                select.focus();
                return;
            }
            selectedTypes.add(select.value);
        });

        if (hasEmptySelection) {
            event.preventDefault();
            alert('Please select an equipment type for all entries.');
            return;
        }
    });

    // Initial update of selects
    updateEquipmentSelects();

            <?php foreach ($maintenancePlans as $plan): ?>
                    (function() {
                        const planId = '<?= $plan['id'] ?>';
                        const container = document.getElementById('equipmentContainer' + planId);
                        const addButton = document.getElementById('addMoreEquipment' + planId);
                        const form = document.getElementById('editPlanForm' + planId);
                        const yearSelect = document.getElementById('year_maintained' + planId);

                        // Debug function
                        function logFormData() {
                            const formData = new FormData(form);
                            console.log("Form Data:");
                            for (let [key, value] of formData.entries()) {
                                console.log(`${key}: ${value}`);
                            }
                        }

                        // Equipment type options HTML with data attributes
                        const equipmentTypesOptions = `
            <?php
                $optionsHtml = '<option value="">--Select Equipment Type--</option>';
                foreach ($equipmentTypes as $type) {
                    $optionsHtml .= '<option value="' . htmlspecialchars($type['equip_type_id']) . '">' .
                        htmlspecialchars($type['equip_type_name']) .
                        '</option>';
                }
                echo str_replace("'", "\\'", $optionsHtml);
            ?>
        `;

                        // Function to update equipment type options
                        function updateEquipmentTypeOptions(currentContainer) {
                            // Collect currently selected equipment type IDs
                            const selectedTypes = Array.from(currentContainer.querySelectorAll('select[name="equipment_types[]"]'))
                                .map(select => select.value)
                                .filter(value => value !== '');

                            // Update all equipment type selects in this container
                            const equipmentSelects = currentContainer.querySelectorAll('select[name="equipment_types[]"]');

                            equipmentSelects.forEach(select => {
                                const currentValue = select.value;

                                // Reset the options
                                select.innerHTML = equipmentTypesOptions;

                                // Disable already selected options
                                selectedTypes.forEach(selectedType => {
                                    if (selectedType !== currentValue) {
                                        const optionToDisable = select.querySelector(`option[value="${selectedType}"]`);
                                        if (optionToDisable) {
                                            optionToDisable.disabled = true;
                                        }
                                    }
                                });

                                // Restore the current select's value
                                select.value = currentValue;
                            });
                        }

                        // Function to create a new equipment entry
                        function createEquipmentEntry() {
                            const newEntry = document.createElement('div');
                            newEntry.className = 'equipment-entry border p-3 mb-3';
                            newEntry.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6>Equipment Entry</h6>
        <button type="button" class="btn btn-danger btn-sm remove-equipment">Remove</button>
    </div>
    <div class="mb-3">
        <label class="form-label">Select Equipment Type:</label>
        <select name="equipment_types[]" class="form-select" required>
            ${equipmentTypesOptions}
        </select>
    </div>
    <div class="row months-container">
        <?php foreach ($months as $month): ?>
            <div class="col-md-3 mb-3">
                <label class="form-label"><?= $month ?></label>
                <input type="number" 
                       class="form-control"
                       min="0"
                       step="0.01"
                       value="0"
                       required>
            </div>
        <?php endforeach; ?>
    </div>
    `;

                            // Add remove functionality to the new entry
                            newEntry.querySelector('.remove-equipment').addEventListener('click', function() {
                                newEntry.remove();
                                updateEquipmentTypeOptions(container);
                            });

                            // Add change event listener to update equipment type and input names
                            const equipmentSelect = newEntry.querySelector('select[name="equipment_types[]"]');
                            const monthInputs = newEntry.querySelectorAll('.months-container input');

                            equipmentSelect.addEventListener('change', function() {
                                // Update input names with selected equipment type
                                monthInputs.forEach(input => {
                                    input.name = `counts[${this.value}][${input.closest('.col-md-3').querySelector('label').textContent}]`;
                                });
                                updateEquipmentTypeOptions(container);
                            });

                            return newEntry;
                        }

                        // Add new equipment type
                        addButton.addEventListener('click', function() {
                            const newEntry = createEquipmentEntry();
                            container.appendChild(newEntry);
                            updateEquipmentTypeOptions(container);
                        });

                        // Form submission validation and logging
                        form.addEventListener('submit', function(event) {
                            // Log form data before submission
                            logFormData();

                            // Ensure year is selected
                            if (!yearSelect.value) {
                                event.preventDefault();
                                alert('Please select a year for the maintenance plan.');
                                yearSelect.focus();
                                return;
                            }

                            // Validate at least one equipment type is selected
                            const equipmentTypes = container.querySelectorAll('select[name="equipment_types[]"]');
                            const uniqueTypes = new Set();
                            let isDuplicate = false;

                            equipmentTypes.forEach(select => {
                                if (select.value) {
                                    if (uniqueTypes.has(select.value)) {
                                        isDuplicate = true;
                                        event.preventDefault();
                                        alert('Duplicate equipment types are not allowed.');
                                        select.focus();
                                        return;
                                    }
                                    uniqueTypes.add(select.value);
                                }
                            });

                            if (isDuplicate) return;

                            // Ensure at least one equipment type
                            if (uniqueTypes.size === 0) {
                                event.preventDefault();
                                alert('Please add at least one equipment type.');
                                return;
                            }
                        });

                        // Initial setup for existing entries
                        updateEquipmentTypeOptions(container);

                        // Delegate remove event for dynamically added entries
                        container.addEventListener('click', function(event) {
                            if (event.target.classList.contains('remove-equipment')) {
                                // Ensure at least one equipment type remains
                                if (container.querySelectorAll('.equipment-entry').length > 1) {
                                    event.target.closest('.equipment-entry').remove();
                                    updateEquipmentTypeOptions(container);
                                } else {
                                    alert('At least one equipment type must remain.');
                                }
                            }
                        });

                        // Add change event to existing selects
                        container.querySelectorAll('select[name="equipment_types[]"]').forEach(select => {
                            select.addEventListener('change', function() {
                                updateEquipmentTypeOptions(container);
                            });
                        });
                    })();
            <?php endforeach; ?>
        });
    </script>
</body>

</html>