<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: index.php");
    exit;
}

// Fetch user details
$user = null; // Initialize $user to null
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
	include 'conn.php';

    try {
        // Create connection
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch user details
        $sql = "SELECT email, firstname, lastname, role FROM user WHERE admin_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            die("User not found.");
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    die("User ID not set in session.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>ICTEMMS</title>
  <style>
    .modal-content {
      background-color: #ffffff;
      border-radius: 24px !important;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1) !important;
      border: none !important;
      padding: 15px;
      max-width: 400px;
      margin-left: 80px;
      margin-top: 80px;
    }

    .modal-header {
      border-bottom: none;
      margin-top: -15px;
    }

    .modal-header h5 {
      color: #3A3A3A;
      font-weight: bold;
      font-size: 13px;
      padding-top: 4px;
    }

    .section-divider {
      border: none;
      height: 1px;
      background-color: #ddd;
      margin-top: 5px;
      margin-bottom: 10px;
    }

    .form-group {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 5px;
    }

    .form-group label {
      font-size: 13px;
      width: 300px;
      padding-top: 5px;
    }

    .form-control {
      height: 33px !important;
      border: 2px solid #646464;
      border-radius: 14px !important;
      color: #646464 !important;
      font-size: 12px !important;
    }

    .modal-body button[type="submit"] {
      width: 130px;
      height: 33px;
      background-color: #a81519;
      color: white;
      font-weight: bold;
      font-size: 12px;
      border: none;
      border-radius: 14px;
      margin-top: 10px;
      margin-left: 110px;
    }

    .modal-body button[type="submit"]:hover {
      background-color: #E3595C;
    }

    .password-section {
      margin-top: 15px;
    }

    .password-section small {
      font-size: 12px;
      color: #646464;
    }

    #password-strength {
      font-size: 12px;
      margin-top: 5px;
      margin-left: 165px;
    }

    .form-group #repeat_password,
    .form-group label[for="repeat_password"] {
      margin-top: 15px;
    }

    #alert-message {
      border-radius: 14px;
      font-size: 12px;
      padding: 10px;
      margin-bottom: 15px;
    }

    .alert-success {
      background-color: #d4edda;
      border-color: #c3e6cb;
      color: #155724;
    }

    .alert-danger {
      background-color: #f8d7da;
      border-color: #f5c6cb;
      color: #721c24;
    }

    /* ========== Custom Logout Modal Styles ========== */
    .logout-overlay {
      display: none; /* hidden by default */
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7); /* darker bg to emphasize the modal */
      justify-content: center;
      align-items: center;
      z-index: 9999; /* ensure it appears on top of everything */
    }

    .logout-modal {
      background: #fff;
      padding: 2rem;
      border-radius: 15px;
      text-align: center;
      max-width: 400px;
      width: 90%;
    }

    .logout-modal h2 {
      margin-bottom: 1.5rem;
      font-size: 1.2rem;
      color: #333;
    }

    .logout-modal-buttons {
      display: flex;
      justify-content: space-around;
      align-items: center;
      margin-top: 1rem;
      gap: 1rem;
    }

    /* Common styling for the modal’s buttons */
    .logout-btn {
      padding: 0.5rem 1.5rem;
      border-radius: 8px;
      border: none;
      font-weight: bold;
      cursor: pointer;
      font-size: 0.9rem;
    }

    /* Maroon button */
    .maroon-btn {
      background-color: #800000; /* maroon */
      color: #fff;
    }
    .maroon-btn:hover {
      background-color: #a81519;
    }

    /* Cancel button hover (if you want a subtle effect) */
    .logout-btn:hover:not(.maroon-btn) {
      background-color: #ddd;
    }
  </style>

  <!-- Bootstrap CSS & Google Icons -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
  <link rel="stylesheet" href="styles.css">
</head>

<body class="dashboard">

  <!-- Header-->
  <div class="header">
    <span id="current-page">Dashboard</span>
    <div class="user-info" id="user-info">
      <span class="material-symbols-rounded">account_box</span>
      <div class="text-info">
        <span class="username">
          <?php echo ucfirst(htmlspecialchars($_SESSION['firstname'])) . ' ' . ucfirst(htmlspecialchars($_SESSION['lastname']));?>
        </span>
        <span class="role">
          <?php echo ucfirst(htmlspecialchars($_SESSION['role']));?>
        </span>
      </div>
    </div>
  </div>

  <!-- Update Profile Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Update Profile</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="alert-message" class="alert" style="display: none; margin-bottom: 15px; padding: 2px; text-align: center;"></div>
          <form id="updateProfileForm" method="POST" action="profile.php">
            <div class="form-group">
              <label for="email">Email:</label>
              <input type="email" class="form-control" id="email" name="email"
                value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
              <label for="firstname">First Name:</label>
              <input type="text" class="form-control" id="firstname" name="firstname"
                value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
            </div>
            <div class="form-group">
              <label for="lastname">Last Name:</label>
              <input type="text" class="form-control" id="lastname" name="lastname"
                value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
            </div>
            <hr>
            <div class="password-section">
              <small class="text-muted mb-3 d-block">Leave password fields empty if you don't want to change your password.</small>
              <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" class="form-control" id="current_password" name="current_password">
              </div>
              <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
              </div>
              <div class="password-texts">
                <div id="password-strength" class="mt-2"></div>
              </div>
              <small class="form-text text-muted">Password must be at least 8 characters long with at least 1 uppercase
                letter, number and symbol.</small>
              <div class="form-group">
                <label for="repeat_password">Repeat New Password:</label>
                <input type="password" class="form-control" id="repeat_password" name="repeat_password">
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Container -->
  <div class="main-container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <img src="assets/images/usep-logo.png" alt="Logo" class="logo">
      </div>
      <ul class="nav-links">
        <li><a href="dashboard-content.php" class="nav-link active" data-title="Dashboard">
          <span class="material-symbols-rounded">home</span>Dashboard</a>
        </li>
        <?php if ($_SESSION['role'] !== 'Assistant'): ?>
          <hr>
          <p style="color: #343a40">Management</p>
          <li><a href="add_users.php" class="nav-link" data-title="User Management">
            <span class="material-symbols-rounded">person_add</span>Users</a>
          </li>
          <li><a href="add_equipment_type.php" class="nav-link" data-title="Equipment Type Management">
            <span class="material-symbols-rounded">devices</span>Equipment Type</a>
          </li>
          <li><a href="add_model.php" class="nav-link" data-title="Equipment Model Management">
            <span class="material-symbols-rounded">dvr</span>Model</a>
          </li>
          <li><a href="add_location.php" class="nav-link" data-title="Location Management">
            <span class="material-symbols-rounded">add_location_alt</span>Location</a>
          </li>
          <li><a href="add_remarks.php" class="nav-link" data-title="Remarks Management">
            <span class="material-symbols-rounded">edit_square</span>Remarks</a>
          </li>
          <li><a href="add_personnel.php" class="nav-link" data-title="Personnel Info Management">
            <span class="material-symbols-rounded">groups_2</span>Personnel</a>
          </li>
          <hr>
        <?php endif; ?>
        <li><a href="equipment_input_ict.php" class="nav-link" data-title="Equipment Registration">
          <span class="material-symbols-rounded">add_box</span>Equipment Registration</a>
        </li>
        <li><a href="plan_maintenance.php" class="nav-link" data-title="Plan Maintenance">
          <span class="material-symbols-rounded">contract_edit</span>Plan Maintenance</a>
        </li>
        <li><a href="equipment_maintenance.php" class="nav-link" data-title="Equipment Maintenance">
          <span class="material-symbols-rounded">build</span>Equipment Maintenance</a>
        </li>
        <li><a href="reports.php" class="nav-link" data-title="Reports">
          <span class="material-symbols-rounded">report</span>Reports</a>
        </li>
        <li>
          <a href="#" onclick="logout()" class="nav-link" data-title="Logout">
            <span class="material-symbols-rounded">logout</span>Logout
          </a>
        </li>
      </ul>
    </div>

    <!-- Main Content (iframe) -->
    <div class="main-content">
      <iframe id="content-frame" class="content-frame" src="dashboard-content.php"></iframe>
    </div>
  </div>

  <!-- jQuery & Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"
          integrity="sha384-UG8ao2jwOWB7/oDdObZc6ItJmwUkR/PfMyt9Qs5AwX7PsnYn1CRKCTWyncPTWvaS"
          crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Main Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // ========== Password Strength Function ==========
      function validatePassword(password) {
        const minLength = password.length >= 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        return {
          valid: minLength && hasUpperCase && hasNumber && hasSymbol,
          errors: {
            length: !minLength,
            uppercase: !hasUpperCase,
            number: !hasNumber,
            symbol: !hasSymbol
          }
        };
      }

      // Update password strength indicator
      $('#new_password').on('input', function() {
        const password = $(this).val().trim();
        const strengthDiv = $('#password-strength');
        const validation = validatePassword(password);

        let strengthHtml = '';
        if (password) {
          strengthHtml += '<div class="password-requirements">';
          strengthHtml += `<div class="${validation.errors.length ? 'text-danger' : 'text-success'}">• 8+ characters</div>`;
          strengthHtml += `<div class="${validation.errors.uppercase ? 'text-danger' : 'text-success'}">• Uppercase letter</div>`;
          strengthHtml += `<div class="${validation.errors.number ? 'text-danger' : 'text-success'}">• Number</div>`;
          strengthHtml += `<div class="${validation.errors.symbol ? 'text-danger' : 'text-success'}">• Symbol</div>`;
          strengthHtml += '</div>';
        }
        strengthDiv.html(strengthHtml);
      });

      // Handle navigation clicks
      document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function (e) {
          // Ignore links that are '#'
          if (this.getAttribute('href') !== '#') {
            e.preventDefault();

            // Update active state
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            // Update page title
            document.getElementById('current-page').textContent = this.dataset.title;

            // Load content in iframe
            document.getElementById('content-frame').src = this.getAttribute('href');
          }
        });
      });

      // Show modal on user-info click
      $('#user-info').on('click', function () {
        $('#editUserModal').modal('show');
      });

      // Handle form submission
      $('#updateProfileForm').on('submit', function(e) {
        e.preventDefault();

        const alertDiv = $('#alert-message');
        const email = $('#email').val().trim();
        const firstname = $('#firstname').val().trim();
        const lastname = $('#lastname').val().trim();
        const currentPassword = $('#current_password').val().trim();
        const newPassword = $('#new_password').val().trim();
        const repeatPassword = $('#repeat_password').val().trim();

        // Validate email and name fields
        if (!email) {
          alertDiv.removeClass('alert-success').addClass('alert-danger')
            .text('Email is required').show();
          return;
        }
        if (!isValidEmail(email)) {
          alertDiv.removeClass('alert-success').addClass('alert-danger')
            .text('Please enter a valid email address').show();
          return;
        }
        if (!firstname) {
          alertDiv.removeClass('alert-success').addClass('alert-danger')
            .text('First name is required').show();
          return;
        }
        if (!lastname) {
          alertDiv.removeClass('alert-success').addClass('alert-danger')
            .text('Last name is required').show();
          return;
        }

        // If password fields are used, validate them
        if (currentPassword || newPassword || repeatPassword) {
          if (!currentPassword) {
            alertDiv.removeClass('alert-success').addClass('alert-danger')
              .text('Please enter your current password').show();
            return;
          }
          if (!newPassword) {
            alertDiv.removeClass('alert-success').addClass('alert-danger')
              .text('Please enter a new password').show();
            return;
          }
          if (!repeatPassword) {
            alertDiv.removeClass('alert-success').addClass('alert-danger')
              .text('Please confirm your new password').show();
            return;
          }

          // Validate password strength
          const validation = validatePassword(newPassword);
          if (!validation.valid) {
            let errorMessage = 'Password must have: ';
            if (validation.errors.length) errorMessage += '8+ characters, ';
            if (validation.errors.uppercase) errorMessage += 'an uppercase letter, ';
            if (validation.errors.number) errorMessage += 'a number, ';
            if (validation.errors.symbol) errorMessage += 'a symbol, ';
            errorMessage = errorMessage.slice(0, -2);

            alertDiv.removeClass('alert-success').addClass('alert-danger')
              .text(errorMessage).show();
            return;
          }
        }

        // Submit form via AJAX
        $.ajax({
          url: 'profile.php',
          type: 'POST',
          dataType: 'json',
          data: $(this).serialize(),
          success: function(response) {
            console.log('Response:', response);
            alertDiv.removeClass('alert-success alert-danger');

            if (response.status === 'success') {
              alertDiv.addClass('alert-success');
              // Clear password fields on success if they were used
              if (currentPassword || newPassword || repeatPassword) {
                $('#current_password, #new_password, #repeat_password').val('');
                $('#password-strength').empty();
              }
              // Update user info in header if changed
              if (response.user) {
                $('.username').text(response.user.firstname + ' ' + response.user.lastname);
              }
            } else {
              alertDiv.addClass('alert-danger');
            }
            alertDiv.text(response.message).fadeIn();

            // Hide alert after 3 seconds if success
            if (response.status === 'success') {
              setTimeout(function() {
                alertDiv.fadeOut();
              }, 3000);
            }
          },
          error: function(xhr, status, error) {
            console.log('Error:', xhr, status, error);
            alertDiv.removeClass('alert-success')
              .addClass('alert-danger')
              .text('An error occurred while updating the profile')
              .fadeIn();
          }
        });
      });

      // Email validation
      function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
      }

      // Hide repeat password field on page load
      $('.form-group label[for="repeat_password"], .form-group #repeat_password').hide();

      // Toggle repeat password field if user types a new password
      $('#new_password').on('input', function() {
        const password = $(this).val().trim();
        if (password.length > 0) {
          $('.form-group label[for="repeat_password"], .form-group #repeat_password').show();
          $('.modal-content').css('margin-top', '30px');
        } else {
          $('.form-group label[for="repeat_password"], .form-group #repeat_password').hide();
          $('.modal-content').css('margin-top', '80px');
        }
      });
    });

    // ========== Custom Logout Function ==========
    function logout() {
      // Show our custom logout overlay (instead of confirm())
      document.getElementById('logoutOverlay').style.display = 'flex';
    }
  </script>

  <!-- Custom Logout Modal (centered, dark background) -->
  <div id="logoutOverlay" class="logout-overlay">
    <div class="logout-modal">
      <h2>Are you sure you want to logout?</h2>
      <div class="logout-modal-buttons">
        <button id="confirmLogout" class="logout-btn maroon-btn">Logout</button>
        <button id="cancelLogout" class="logout-btn">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Script for handling the custom logout modal -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const overlay = document.getElementById('logoutOverlay');
      const confirmBtn = document.getElementById('confirmLogout');
      const cancelBtn = document.getElementById('cancelLogout');

      // Confirm logout
      confirmBtn.addEventListener('click', function () {
        window.location.href = 'logout.php';
      });

      // Cancel logout
      cancelBtn.addEventListener('click', function () {
        overlay.style.display = 'none';
      });
    });
  </script>
</body>
</html>
