<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setDefaultController('Login');

$routes->get('/', 'Login::index');
$routes->get('login', 'Login::index');
$routes->post('login', 'Login::index');
$routes->post('migrate', 'Login::migrate');

$routes->get('register_shifts', 'RegisterShifts::getIndex');
$routes->post('register_shifts/open', 'RegisterShifts::postOpen');
$routes->post('register_shifts/close', 'RegisterShifts::postClose');
$routes->post('register_shifts/movement', 'RegisterShifts::postMovement');
$routes->get('payment_reconciliation', 'PaymentReconciliations::getIndex');
$routes->post('payment_reconciliation/save', 'PaymentReconciliations::postSave');
$routes->get('sales', 'Sales::getIndex');
$routes->get('sales/index', 'Sales::getIndex');
$routes->post('sales/add', 'Sales::postAdd');
$routes->post('sales/addPayment', 'Sales::postAddPayment');
$routes->post('sales/cancel', 'Sales::postCancel');
$routes->post('sales/changeItemDescription', 'Sales::postChangeItemDescription');
$routes->post('sales/changeItemName', 'Sales::postChangeItemName');
$routes->post('sales/changeItemNumber', 'Sales::postChangeItemNumber');
$routes->post('sales/changeMode', 'Sales::postChangeMode');
$routes->post('sales/checkInvoiceNumber', 'Sales::postCheckInvoiceNumber');
$routes->post('sales/complete', 'Sales::postComplete');
$routes->post('sales/delete', 'Sales::postDelete');
$routes->match(['get', 'post'], 'sales/deleteItem/(:num)', 'Sales::getDeleteItem/$1');
$routes->match(['get', 'post'], 'sales/deletePayment/(:any)', 'Sales::getDeletePayment/$1');
$routes->get('sales/delivery_note/(:num)', 'Sales::getDeliveryNote/$1');
$routes->get('sales/discard_suspended_sale', 'Sales::getDiscardSuspendedSale');
$routes->get('sales/discardsuspendedsale', 'Sales::getDiscardSuspendedSale');
$routes->get('sales/edit/(:num)', 'Sales::getEdit/$1');
$routes->post('sales/editItem/(:segment)', 'Sales::postEditItem/$1');
$routes->get('sales/invoice/(:num)', 'Sales::getInvoice/$1');
$routes->get('sales/itemSearch', 'Sales::getItemSearch');
$routes->get('sales/manage', 'Sales::getManage');
$routes->get('sales/priceApprovals', 'Sales::getPriceApprovals');
$routes->post('sales/priceApproval/(:num)', 'Sales::postPriceApproval/$1');
$routes->get('sales/receipt/(:num)', 'Sales::getReceipt/$1');
$routes->match(['get', 'post'], 'sales/removeCustomer', 'Sales::getRemoveCustomer');
$routes->get('sales/row/(:num)', 'Sales::getRow/$1');
$routes->get('sales/salesKeyboardHelp', 'Sales::getSalesKeyboardHelp');
$routes->post('sales/save/(:num)', 'Sales::postSave/$1');
$routes->post('sales/save', 'Sales::postSave');
$routes->get('sales/search', 'Sales::getSearch');
$routes->post('sales/selectCustomer', 'Sales::postSelectCustomer');
$routes->get('sales/sendPdf/(:num)', 'Sales::getSendPdf/$1');
$routes->get('sales/sendPdf/(:num)/(:segment)', 'Sales::getSendPdf/$1/$2');
$routes->get('sales/sendpdf/(:num)', 'Sales::getSendPdf/$1');
$routes->get('sales/sendpdf/(:num)/(:segment)', 'Sales::getSendPdf/$1/$2');
$routes->get('sales/sendReceipt/(:num)', 'Sales::getSendReceipt/$1');
$routes->get('sales/sendreceipt/(:num)', 'Sales::getSendReceipt/$1');
$routes->post('sales/setComment', 'Sales::postSetComment');
$routes->post('sales/setEmailReceipt', 'Sales::postSetEmailReceipt');
$routes->post('sales/setInvoiceNumber', 'Sales::postSetInvoiceNumber');
$routes->post('sales/setPaymentType', 'Sales::postSetPaymentType');
$routes->post('sales/setPriceWorkOrders', 'Sales::postSetPriceWorkOrders');
$routes->post('sales/setPrintAfterSale', 'Sales::postSetPrintAfterSale');
$routes->post('sales/setQuoteDetails', 'Sales::postSetQuoteDetails');
$routes->post('sales/suspend', 'Sales::postSuspend');
$routes->get('sales/suspended', 'Sales::getSuspended');
$routes->post('sales/unsuspend', 'Sales::postUnsuspend');
$routes->get('purchase_orders', 'PurchaseOrders::getIndex');
$routes->post('purchase_orders/approve/(:num)', 'PurchaseOrders::postApprove/$1');
$routes->post('purchase_orders/receive/(:num)', 'PurchaseOrders::postReceive/$1');
$routes->post('purchase_orders/cancel/(:num)', 'PurchaseOrders::postCancel/$1');
$routes->post('receivings/savePurchaseOrder', 'Receivings::postSavePurchaseOrder');
$routes->get('salary_structures', 'Salary_structures::getIndex');
$routes->get('salary_structures/view/(:num)', 'Salary_structures::getView/$1');
$routes->post('salary_structures/save/(:num)', 'Salary_structures::postSave/$1');
$routes->get('payroll', 'Payroll::getIndex');
$routes->get('payroll/report', 'Payroll::getReport');
$routes->get('payroll/search', 'Payroll::getSearch');
$routes->get('payroll/view', 'Payroll::getView');
$routes->get('payroll/view/(:num)', 'Payroll::getView/$1');
$routes->post('payroll/save/(:segment)', 'Payroll::postSave/$1');
$routes->post('payroll/save', 'Payroll::postSave');
$routes->get('payroll/items/(:num)', 'Payroll::getItems/$1');
$routes->get('payroll/payslip/(:num)', 'Payroll::getPayslip/$1');
$routes->post('payroll/approve/(:num)', 'Payroll::postApprove/$1');
$routes->post('payroll/pay/(:num)', 'Payroll::postPay/$1');
$routes->post('payroll/delete', 'Payroll::postDelete');
$routes->post('stock_transfers/approve/(:num)', 'StockTransfers::postApprove/$1');
$routes->post('stock_transfers/dispatch/(:num)', 'StockTransfers::postDispatch/$1');
$routes->post('stock_transfers/receive/(:num)', 'StockTransfers::postReceive/$1');
$routes->post('stock_transfers/cancel/(:num)', 'StockTransfers::postCancel/$1');

$routes->get('projects', 'Projects::getIndex');
$routes->get('projects/search', 'Projects::getSearch');
$routes->get('projects/view', 'Projects::getView');
$routes->get('projects/view/(:num)', 'Projects::getView/$1');
$routes->get('projects/row/(:num)', 'Projects::getRow/$1');
$routes->post('projects/save/(:segment)', 'Projects::postSave/$1');
$routes->post('projects/save', 'Projects::postSave');
$routes->post('projects/delete', 'Projects::postDelete');
$routes->post('projects/linkSale/(:num)', 'Projects::postLinkSale/$1');
$routes->post('projects/unlinkSale/(:num)', 'Projects::postUnlinkSale/$1');
$routes->post('projects/linkExpense/(:num)', 'Projects::postLinkExpense/$1');
$routes->post('projects/unlinkExpense/(:num)', 'Projects::postUnlinkExpense/$1');

$routes->get('bookmarks', 'Bookmarks::getIndex');
$routes->post('bookmarks/add', 'Bookmarks::postAdd');
$routes->post('bookmarks/remove/(:num)', 'Bookmarks::postRemove/$1');

$routes->get('notifications', 'Notifications::getIndex');
$routes->get('notifications/unreadCount', 'Notifications::getUnreadCount');
$routes->post('notifications/markRead/(:num)', 'Notifications::postMarkRead/$1');
$routes->post('notifications/markAllRead', 'Notifications::postMarkAllRead');

$routes->get('discounts', 'Discounts::getIndex');
$routes->get('discounts/search', 'Discounts::getSearch');
$routes->get('discounts/view/(:segment)', 'Discounts::getView/$1');
$routes->get('discounts/row/(:num)', 'Discounts::getRow/$1');
$routes->post('discounts/save/(:segment)', 'Discounts::postSave/$1');
$routes->post('discounts/save', 'Discounts::postSave');
$routes->post('discounts/delete', 'Discounts::postDelete');
$routes->post('discounts/validate', 'Discounts::postValidate');

$routes->add('no_access/index/(:segment)', 'No_access::index/$1');
$routes->add('no_access/index/(:segment)/(:segment)', 'No_access::index/$1/$2');

$routes->add('reports/summary_locations/(:segment)/(:segment)/(:segment)/(:segment)/(:segment)', 'Reports::summary_locations/$1/$2/$3/$4');
$routes->add('reports/summary_(:any)/(:any)/(:any)', 'Reports::Summary_$1/$2/$3/$4');
$routes->add('reports/summary_expenses_categories', 'Reports::date_input_only');
$routes->add('reports/summary_payments', 'Reports::date_input_only');
$routes->add('reports/summary_discounts', 'Reports::summary_discounts_input');
$routes->add('reports/summary_(:any)', 'Reports::date_input');

$routes->add('reports/graphical_(:any)/(:any)/(:any)', 'Reports::Graphical_$1/$2/$3/$4');
$routes->add('reports/graphical_summary_expenses_categories', 'Reports::date_input_only');
$routes->add('reports/graphical_summary_discounts', 'Reports::summary_discounts_input');
$routes->add('reports/graphical_(:any)', 'Reports::date_input');

$routes->add('reports/inventory_(:any)/(:any)', 'Reports::Inventory_$1/$2');
$routes->add('reports/inventory_low', 'Reports::inventory_low');
$routes->add('reports/inventory_summary', 'Reports::inventory_summary_input');
$routes->add('reports/inventory_summary/(:any)/(:any)/(:any)', 'Reports::inventory_summary/$1/$2/$3');

$routes->add('reports/detailed_item_sales', 'Reports::dateInputItemSales');
$routes->add('reports/detailed_item_sales/(:segment)/(:segment)/(:segment)/(:segment)/(:segment)', 'Reports::detailedItemSales/$1/$2/$3/$4/$5');
$routes->add('reports/detailed_(:any)/(:any)/(:any)/(:any)', 'Reports::Detailed_$1/$2/$3/$4');
$routes->add('reports/detailed_sales', 'Reports::date_input_sales');
$routes->add('reports/detailed_receivings', 'Reports::date_input_recv');

$routes->add('reports/specific_(:any)/(:any)/(:any)/(:any)', 'Reports::Specific_$1/$2/$3/$4');
$routes->add('reports/specific_customers', 'Reports::specific_customer_input');
$routes->add('reports/specific_employees', 'Reports::specific_employee_input');
$routes->add('reports/specific_discounts', 'Reports::specific_discount_input');
$routes->add('reports/specific_suppliers', 'Reports::specific_supplier_input');
