<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Questions - Brew Haven</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/question.css">
    <link rel="stylesheet" href="../css/brew-haven.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/security.js?v=<?php echo @filemtime(__DIR__ . '/../js/security.js'); ?>"></script>
</head>

<body class="bg-register security-questions-page">

    
    <header class="main-header">
        <div class="header-content">
            <h1><img src="../images/brew-haven-logo.svg" alt="" style="width:1.2em;height:1.2em;vertical-align:-0.22em;margin-right:0.3rem;"> Brew Haven</h1>
            <div class="header-actions">
                <a href="index.php" class="nav-link">Home</a>
                <a href="login.php" class="nav-link">Login</a>
            </div>
        </div>
    </header>

    <main class="hero">
        <div class="hero-inner">
            <!-- Left Side - Security Questions Form -->
            <div class="left-side">
                <div class="form-container">
                    <form class="form-box register-form" autocomplete="off">
                        <h2 class="full-row">Security Questions</h2> 
                        <div class="register-grid auth-questions-center">
                            <div>
                                <label for="auth_question1">Question 1<span style="color:red">*</span>:</label>
                                <select id="auth_question1" name="auth_question1">
                                    <option value="">-- Select Question --</option>
                                    <option value="Who is your best friend in Elementary?">Who is your best friend in Elementary?</option>
                                    <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
                                    <option value="Who is your favorite teacher in high school?">Who is your favorite teacher in high school?</option>
                                </select>
                                <div class="error-message" id="auth_question1-error">&nbsp;</div>
                                <div class="input-with-toggle">
                                    <input type="password" id="auth_answer1" name="auth_answer1" placeholder="Your answer" autocomplete="off">
                                    <i class="fa-solid fa-eye togele-eye" data-target="auth_answer1"></i>
                                </div>
                                <div class="error-message" id="auth_answer1-error">&nbsp;</div>
                            </div>
                            <div>
                                <label for="auth_question2">Question 2<span style="color:red">*</span>:</label>
                                <select id="auth_question2" name="auth_question2">
                                    <option value="">-- Select Question --</option>
                                    <option value="What is your favorite sport?">What is your favorite sport?</option>
                                    <option value="What is your favorite movie?">What is your favorite movie?</option>
                                    <option value="What is your favorite color?">What is your favorite color?</option>
                                </select>
                                <div class="error-message" id="auth_question2-error">&nbsp;</div>
                                <div class="input-with-toggle">
                                    <input type="password" id="auth_answer2" name="auth_answer2" placeholder="Your answer" autocomplete="off">
                                    <i class="fa-solid fa-eye togele-eye" data-target="auth_answer2"></i>
                                </div>
                                <div class="error-message" id="auth_answer2-error">&nbsp;</div>
                            </div>
                            <div>
                                <label for="auth_question3">Question 3<span style="color:red">*</span>:</label>
                                <select id="auth_question3" name="auth_question3">
                                    <option value="">-- Select Question --</option>
                                    <option value="What is your favorite fruit?">What is your favorite fruit?</option>
                                    <option value="Do you like to eat vegetables?">Do you like to eat vegetables?</option>
                                    <option value="What is your favorite snack?">What is your favorite snack?</option>
                                </select>
                                <div class="error-message" id="auth_question3-error">&nbsp;</div>
                                <div class="input-with-toggle">
                                    <input type="password" id="auth_answer3" name="auth_answer3" placeholder="Your answer" autocomplete="off">
                                    <i class="fa-solid fa-eye togele-eye" data-target="auth_answer3"></i>
                                </div>
                                <div class="error-message" id="auth_answer3-error">&nbsp;</div>                                
                            </div>
                        </div>    
                            
                        <div class="full-row button-row">
                            <button type="button" id="prev-button" class="prev-btn">Back</button>
                            <button type="submit" id="next-button" class="next-btn">Register</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <span>&copy; 2025 Brew Haven</span>
        </div>
    </footer>
    
    <script src="../js/toggle-visibility.js?v=<?php echo @filemtime(__DIR__ . '/../js/toggle-visibility.js'); ?>"></script>
    <script src="../js/question.js?v=<?php echo @filemtime(__DIR__ . '/../js/question.js'); ?>"></script>
    <script src="../js/question-session-storage.js?v=<?php echo @filemtime(__DIR__ . '/../js/question-session-storage.js'); ?>"></script>
</body>
</html>

