<?php
/**
 * Quick Router Verification Script
 * 
 * Place this file in your Joomla root and access via browser:
 * http://yoursite.com/verify-router.php
 * 
 * This will check if the router patch is properly installed.
 */

// Prevent direct access in production
define('_JEXEC', 1);

// Set paths
define('JPATH_BASE', dirname(__FILE__));
define('JPATH_SITE', JPATH_BASE);
define('JPATH_ROOT', JPATH_BASE);

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Solidres Router Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .check {
            margin: 15px 0;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #ddd;
        }
        .check.success {
            background: #e8f5e9;
            border-left-color: #4CAF50;
        }
        .check.error {
            background: #ffebee;
            border-left-color: #f44336;
        }
        .check.warning {
            background: #fff3e0;
            border-left-color: #ff9800;
        }
        .check-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .icon {
            font-size: 20px;
            margin-right: 10px;
        }
        .code {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 10px;
            overflow-x: auto;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #e3f2fd;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Solidres Router Verification</h1>
        
        <?php
        $errors = 0;
        $warnings = 0;
        $routerPath = JPATH_BASE . '/components/com_solidres/router.php';
        
        // Check 1: Router file exists
        echo '<div class="check ' . (file_exists($routerPath) ? 'success' : 'error') . '">';
        echo '<div class="check-title">';
        echo (file_exists($routerPath) ? '✅' : '❌') . ' Router File Location';
        echo '</div>';
        if (file_exists($routerPath)) {
            echo 'Router file found at: <code>components/com_solidres/router.php</code>';
            $filesize = filesize($routerPath);
            $lines = count(file($routerPath));
            echo '<div class="code">File size: ' . number_format($filesize) . ' bytes<br>';
            echo 'Lines: ' . $lines . ' (expected ~278)</div>';
        } else {
            echo '❌ Router file NOT found at: <code>components/com_solidres/router.php</code>';
            echo '<div class="code">Expected location: ' . $routerPath . '</div>';
            $errors++;
        }
        echo '</div>';
        
        if (file_exists($routerPath)) {
            $routerContent = file_get_contents($routerPath);
            
            // Check 2: SolidresRouter class
            echo '<div class="check ' . (strpos($routerContent, 'class SolidresRouter') !== false ? 'success' : 'error') . '">';
            echo '<div class="check-title">';
            echo (strpos($routerContent, 'class SolidresRouter') !== false ? '✅' : '❌') . ' SolidresRouter Class';
            echo '</div>';
            if (strpos($routerContent, 'class SolidresRouter') !== false) {
                echo 'SolidresRouter class definition found';
            } else {
                echo '❌ SolidresRouter class NOT found';
                $errors++;
            }
            echo '</div>';
            
            // Check 3: findItemIdForContext method
            echo '<div class="check ' . (strpos($routerContent, 'function findItemIdForContext') !== false ? 'success' : 'error') . '">';
            echo '<div class="check-title">';
            echo (strpos($routerContent, 'function findItemIdForContext') !== false ? '✅' : '❌') . ' findItemIdForContext Method';
            echo '</div>';
            if (strpos($routerContent, 'function findItemIdForContext') !== false) {
                echo 'findItemIdForContext method found';
                
                // Check if it's being called
                if (strpos($routerContent, '$this->findItemIdForContext(') !== false) {
                    echo '<br>✅ Method is being called in parse()';
                } else {
                    echo '<br>⚠️ Method exists but may not be called';
                    $warnings++;
                }
            } else {
                echo '❌ findItemIdForContext method NOT found - CRITICAL!';
                echo '<div class="code">This method is required for the fix to work.</div>';
                $errors++;
            }
            echo '</div>';
            
            // Check 4: extractPathContext method
            echo '<div class="check ' . (strpos($routerContent, 'function extractPathContext') !== false ? 'success' : 'error') . '">';
            echo '<div class="check-title">';
            echo (strpos($routerContent, 'function extractPathContext') !== false ? '✅' : '❌') . ' extractPathContext Method';
            echo '</div>';
            if (strpos($routerContent, 'function extractPathContext') !== false) {
                echo 'extractPathContext method found';
            } else {
                echo '❌ extractPathContext method NOT found';
                $errors++;
            }
            echo '</div>';
            
            // Check 5: Task-based routing logic
            echo '<div class="check ' . (strpos($routerContent, "strpos(\$task, '.')") !== false ? 'success' : 'error') . '">';
            echo '<div class="check-title">';
            echo (strpos($routerContent, "strpos(\$task, '.')") !== false ? '✅' : '❌') . ' Task-Based Routing Logic';
            echo '</div>';
            if (strpos($routerContent, "strpos(\$task, '.')") !== false) {
                echo 'Task-based routing detection found';
                
                // Check for format check
                if (strpos($routerContent, '$format === \'json\'') !== false || strpos($routerContent, '$format === "json"') !== false) {
                    echo '<br>✅ JSON format check found';
                } else {
                    echo '<br>⚠️ JSON format check may be missing';
                    $warnings++;
                }
            } else {
                echo '❌ Task-based routing logic NOT found';
                $errors++;
            }
            echo '</div>';
            
            // Check 6: Global routing functions
            $hasBuildRoute = strpos($routerContent, 'function SolidresBuildRoute') !== false;
            $hasParseRoute = strpos($routerContent, 'function SolidresParseRoute') !== false;
            
            echo '<div class="check ' . ($hasBuildRoute && $hasParseRoute ? 'success' : 'error') . '">';
            echo '<div class="check-title">';
            echo ($hasBuildRoute && $hasParseRoute ? '✅' : '❌') . ' Global Routing Functions';
            echo '</div>';
            if ($hasBuildRoute) {
                echo '✅ SolidresBuildRoute function found<br>';
            } else {
                echo '❌ SolidresBuildRoute function NOT found<br>';
                $errors++;
            }
            if ($hasParseRoute) {
                echo '✅ SolidresParseRoute function found';
            } else {
                echo '❌ SolidresParseRoute function NOT found';
                $errors++;
            }
            echo '</div>';
        }
        
        // Check 7: Controller
        $controllerPath = JPATH_BASE . '/components/com_solidres/controllers/reservationasset.php';
        echo '<div class="check ' . (file_exists($controllerPath) ? 'success' : 'warning') . '">';
        echo '<div class="check-title">';
        echo (file_exists($controllerPath) ? '✅' : '⚠️') . ' Controller File';
        echo '</div>';
        if (file_exists($controllerPath)) {
            echo 'ReservationAsset controller found';
            
            $controllerContent = file_get_contents($controllerPath);
            if (strpos($controllerContent, 'function updatePaymentMethod') !== false) {
                echo '<br>✅ updatePaymentMethod method exists';
            } else {
                echo '<br>⚠️ updatePaymentMethod method NOT found in controller';
                echo '<div class="code">This method is needed for payment updates.</div>';
                $warnings++;
            }
        } else {
            echo '⚠️ Controller file not found (may need to be created)';
            echo '<div class="code">' . $controllerPath . '</div>';
            $warnings++;
        }
        echo '</div>';
        
        // Check 8: Configuration
        $configPath = JPATH_BASE . '/configuration.php';
        if (file_exists($configPath)) {
            $configContent = file_get_contents($configPath);
            $debugEnabled = strpos($configContent, "public \$debug = '1'") !== false || 
                           strpos($configContent, 'public $debug = 1') !== false;
            
            echo '<div class="check ' . ($debugEnabled ? 'success' : 'warning') . '">';
            echo '<div class="check-title">';
            echo ($debugEnabled ? '✅' : '⚠️') . ' Debug Mode';
            echo '</div>';
            if ($debugEnabled) {
                echo 'Debug mode is enabled - good for troubleshooting';
            } else {
                echo '⚠️ Debug mode is disabled';
                echo '<div class="code">Enable debug mode in configuration.php for troubleshooting:<br>';
                echo "public \$debug = '1';</div>";
                $warnings++;
            }
            echo '</div>';
        }
        
        // Summary
        echo '<div class="summary">';
        echo '<h2>📊 Summary</h2>';
        
        if ($errors === 0 && $warnings === 0) {
            echo '<div style="color: #4CAF50; font-size: 18px; font-weight: bold;">✅ All checks passed!</div>';
            echo '<p>The router patch appears to be correctly installed.</p>';
            echo '<p>If you\'re still experiencing 404 errors:</p>';
            echo '<ol>';
            echo '<li>Clear Joomla cache (System → Clear Cache)</li>';
            echo '<li>Restart PHP/PHP-FPM (systemctl restart php-fpm)</li>';
            echo '<li>Check that menu items exist for com_solidres</li>';
            echo '<li>Enable logging and check logs/com_solidres.routing.php</li>';
            echo '</ol>';
        } elseif ($errors === 0) {
            echo '<div style="color: #ff9800; font-size: 18px; font-weight: bold;">⚠️ ' . $warnings . ' warning(s) found</div>';
            echo '<p>The router patch is installed but some optional components are missing.</p>';
            echo '<p>The warnings above should be reviewed.</p>';
        } else {
            echo '<div style="color: #f44336; font-size: 18px; font-weight: bold;">❌ ' . $errors . ' error(s) and ' . $warnings . ' warning(s) found</div>';
            echo '<p><strong>CRITICAL ISSUES DETECTED!</strong></p>';
            echo '<p>Recommended fix:</p>';
            echo '<div class="code">';
            echo '# Copy the complete router patch:<br>';
            echo 'cp patches/router.php components/com_solidres/router.php<br>';
            echo '<br>';
            echo '# Clear cache:<br>';
            echo 'rm -rf cache/* administrator/cache/*<br>';
            echo '<br>';
            echo '# Restart PHP:<br>';
            echo 'systemctl restart php-fpm';
            echo '</div>';
        }
        
        echo '<p style="margin-top: 20px;">For detailed troubleshooting, see: <code>TROUBLESHOOTING_404.md</code></p>';
        echo '<a href="TROUBLESHOOTING_404.md" class="button">View Troubleshooting Guide</a>';
        echo '</div>';
        
        echo '<div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 4px; font-size: 12px; color: #666;">';
        echo '<strong>Note:</strong> Delete this verification file after use for security.<br>';
        echo '<code>rm ' . basename(__FILE__) . '</code>';
        echo '</div>';
        ?>
        
    </div>
</body>
</html>
