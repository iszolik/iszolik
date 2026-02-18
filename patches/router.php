<?php
/**
 * Solidres Enhanced Component Router
 * 
 * This router adds support for task-based AJAX routing in submenu contexts.
 * It fixes the 404 error that occurs when AJAX requests are made from submenu
 * pages because Joomla cannot match the Itemid to the menu item context.
 * 
 * Installation:
 * Place this file at: components/com_solidres/router.php
 * 
 * @package     Solidres
 * @subpackage  Router
 */

defined('_JEXEC') or die;

/**
 * Solidres Component Router
 */
class SolidresRouter extends JComponentRouterBase
{
    /**
     * Build method for SEF URLs
     * 
     * @param   array  &$query  The query parameters
     * 
     * @return  array  The URL segments
     */
    public function build(&$query)
    {
        $segments = array();
        
        // Handle view
        if (isset($query['view'])) {
            $segments[] = $query['view'];
            unset($query['view']);
        }
        
        // Handle ID
        if (isset($query['id'])) {
            $segments[] = $query['id'];
            unset($query['id']);
        }
        
        return $segments;
    }

    /**
     * Parse method for SEF URLs with enhanced task-based routing
     * 
     * This method has been enhanced to properly handle task-based AJAX requests
     * in submenu contexts, bypassing the strict menu item validation that causes 404s.
     * 
     * @param   array  &$segments  The URL segments
     * 
     * @return  array  The query parameters
     */
    public function parse(&$segments)
    {
        $vars = array();
        $app = JFactory::getApplication();
        $input = $app->input;
        $menu = $app->getMenu();
        $active = $menu->getActive();
        
        // Get current request details
        $task = $input->get('task', '');
        $format = $input->get('format', '');
        $itemid = $input->getInt('Itemid', 0);
        $uri = JUri::getInstance();
        $path = $uri->getPath();
        
        // Log routing attempt (if logging is configured)
        if (class_exists('JLog')) {
            JLog::add(
                sprintf(
                    'Solidres Router Parse - Path: %s, Segments: %s, Active Menu: %s, Itemid: %s, Task: %s, Format: %s',
                    $path,
                    json_encode($segments),
                    $active ? $active->id : 'none',
                    $itemid,
                    $task,
                    $format
                ),
                JLog::DEBUG,
                'com_solidres.routing'
            );
        }
        
        // ENHANCED: Check if this is a task-based AJAX request
        // Task format: controller.method (e.g., "reservationasset.updatePaymentMethod")
        if (!empty($task) && strpos($task, '.') !== false) {
            // This is a task-based route
            list($controller, $method) = explode('.', $task, 2);
            
            // For AJAX/JSON requests, bypass menu item requirement
            if ($format === 'json' || $format === 'raw') {
                // Set controller and task in vars
                $vars['view'] = $controller;
                $vars['task'] = $task;
                
                // CRITICAL FIX: Force menu context resolution for submenu paths
                if (!$active || ($active && $active->id != $itemid)) {
                    // No active menu or wrong menu - find correct one
                    $correctItemId = $this->findItemIdForContext($path, $itemid);
                    
                    if ($correctItemId) {
                        // Set the correct active menu
                        $menu->setActive($correctItemId);
                        
                        // Update Itemid in input
                        $input->set('Itemid', $correctItemId);
                        
                        if (class_exists('JLog')) {
                            JLog::add(
                                sprintf(
                                    'Solidres Router - Corrected Menu Context: Original Itemid=%s, Corrected Itemid=%s',
                                    $itemid,
                                    $correctItemId
                                ),
                                JLog::INFO,
                                'com_solidres.routing'
                            );
                        }
                    }
                }
                
                // Flag that we've handled routing
                $input->set('_solidres_routed', true);
                
                // Return vars without requiring segment parsing
                if (class_exists('JLog')) {
                    JLog::add(
                        sprintf('Solidres Router - Task-based route parsed: %s', json_encode($vars)),
                        JLog::DEBUG,
                        'com_solidres.routing'
                    );
                }
                
                return $vars;
            }
        }
        
        // Standard routing for non-task URLs
        if (!empty($segments)) {
            // First segment is typically the view
            $vars['view'] = array_shift($segments);
            
            // Second segment might be an ID
            if (!empty($segments)) {
                $vars['id'] = array_shift($segments);
            }
        }
        
        return $vars;
    }
    
    /**
     * Find the correct Itemid for the current path context
     * 
     * This method searches for menu items that match the current request path,
     * which is critical for submenu and hub contexts.
     * 
     * @param   string  $currentPath  The current request path (e.g., "/property1/index.php")
     * @param   int     $requestedId  The originally requested Itemid
     * 
     * @return  int|null  The correct Itemid or null if not found
     */
    protected function findItemIdForContext($currentPath, $requestedId)
    {
        $app = JFactory::getApplication();
        $menu = $app->getMenu();
        
        // Get all menu items for this component
        $items = $menu->getItems('component', 'com_solidres');
        
        if (empty($items)) {
            return null;
        }
        
        // Extract context from path (e.g., "/property1/" from "/property1/index.php")
        $pathContext = $this->extractPathContext($currentPath);
        
        // Try to find a menu item that matches the path context
        foreach ($items as $item) {
            if (!$item->published) {
                continue;
            }
            
            // Get the full route for this menu item
            $itemRoute = $menu->getRoute($item->id);
            $itemPath = JRoute::_($itemRoute, false);
            $itemContext = $this->extractPathContext($itemPath);
            
            // Check if contexts match
            if ($pathContext === $itemContext) {
                return $item->id;
            }
        }
        
        // If no exact match, try the requested ID if it's valid
        if ($requestedId > 0) {
            $requestedItem = $menu->getItem($requestedId);
            if ($requestedItem && $requestedItem->component === 'com_solidres' && $requestedItem->published) {
                return $requestedId;
            }
        }
        
        // Last resort: return the first published Solidres menu item
        foreach ($items as $item) {
            if ($item->published) {
                return $item->id;
            }
        }
        
        return null;
    }
    
    /**
     * Extract context path from a full path
     * 
     * Examples:
     * "/property1/index.php" -> "/property1"
     * "/hub/property2/index.php" -> "/hub/property2"
     * "/index.php" -> ""
     * 
     * @param   string  $path  The full path
     * 
     * @return  string  The context path
     */
    protected function extractPathContext($path)
    {
        // Remove index.php and trailing slashes
        $path = str_replace('index.php', '', $path);
        $path = trim($path, '/');
        
        // If empty, it's root context
        if (empty($path)) {
            return '';
        }
        
        // Return the path parts as context
        return '/' . $path;
    }
}

/**
 * Solidres router functions
 * 
 * These functions are required by Joomla's routing system.
 */

/**
 * Build SEF URL
 * 
 * @param   array  &$query  The query parameters
 * 
 * @return  array  The URL segments
 */
function SolidresBuildRoute(&$query)
{
    $router = new SolidresRouter;
    return $router->build($query);
}

/**
 * Parse SEF URL
 * 
 * @param   array  &$segments  The URL segments
 * 
 * @return  array  The query parameters
 */
function SolidresParseRoute(&$segments)
{
    $router = new SolidresRouter;
    return $router->parse($segments);
}
