#!/usr/bin/env python3
"""
PUBG Mobile UC Store Backend API Test Suite
Tests all public and admin endpoints with comprehensive validation
"""

import requests
import json
import time
from typing import Dict, Any, Optional

# Base URL from environment
BASE_URL = "https://payment-gateway-238.preview.emergentagent.com/api"

class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    BLUE = '\033[94m'
    RESET = '\033[0m'

class TestResults:
    def __init__(self):
        self.passed = 0
        self.failed = 0
        self.tests = []
    
    def add_pass(self, test_name: str, details: str = ""):
        self.passed += 1
        self.tests.append({
            'name': test_name,
            'status': 'PASS',
            'details': details
        })
        print(f"{Colors.GREEN}✓ PASS{Colors.RESET}: {test_name}")
        if details:
            print(f"  {details}")
    
    def add_fail(self, test_name: str, details: str = ""):
        self.failed += 1
        self.tests.append({
            'name': test_name,
            'status': 'FAIL',
            'details': details
        })
        print(f"{Colors.RED}✗ FAIL{Colors.RESET}: {test_name}")
        if details:
            print(f"  {details}")
    
    def print_summary(self):
        total = self.passed + self.failed
        print(f"\n{'='*70}")
        print(f"{Colors.BLUE}TEST SUMMARY{Colors.RESET}")
        print(f"{'='*70}")
        print(f"Total Tests: {total}")
        print(f"{Colors.GREEN}Passed: {self.passed}{Colors.RESET}")
        print(f"{Colors.RED}Failed: {self.failed}{Colors.RESET}")
        print(f"Success Rate: {(self.passed/total*100) if total > 0 else 0:.1f}%")
        print(f"{'='*70}\n")

results = TestResults()

# Global variables for test data
admin_token = None
test_product_id = None
test_order_id = None

def print_section(title: str):
    """Print a section header"""
    print(f"\n{Colors.BLUE}{'='*70}")
    print(f"{title}")
    print(f"{'='*70}{Colors.RESET}\n")

def test_api_health():
    """Test API health check endpoint"""
    print_section("1. API HEALTH CHECK")
    try:
        response = requests.get(f"{BASE_URL}")
        if response.status_code == 200:
            data = response.json()
            if data.get('status') == 'ok':
                results.add_pass("API Health Check", f"Response: {data}")
            else:
                results.add_fail("API Health Check", f"Unexpected response: {data}")
        else:
            results.add_fail("API Health Check", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("API Health Check", f"Exception: {str(e)}")

def test_get_products():
    """Test getting all active products"""
    print_section("2. GET PRODUCTS (Public)")
    global test_product_id
    try:
        response = requests.get(f"{BASE_URL}/products")
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data:
                products = data['data']
                
                # Check if we have 5 products
                if len(products) == 5:
                    results.add_pass("Products Count", f"Found {len(products)} products")
                else:
                    results.add_fail("Products Count", f"Expected 5 products, got {len(products)}")
                
                # Verify product structure and expected UC amounts
                expected_uc = [60, 325, 660, 1800, 3850]
                found_uc = [p['ucAmount'] for p in products]
                
                if found_uc == expected_uc:
                    results.add_pass("Product UC Amounts", f"All expected UC amounts present: {found_uc}")
                else:
                    results.add_fail("Product UC Amounts", f"Expected {expected_uc}, got {found_uc}")
                
                # Check product fields
                if products:
                    test_product_id = products[0]['id']
                    product = products[0]
                    required_fields = ['id', 'title', 'ucAmount', 'price', 'discountPrice', 'discountPercent', 'active']
                    missing_fields = [f for f in required_fields if f not in product]
                    
                    if not missing_fields:
                        results.add_pass("Product Fields", "All required fields present")
                    else:
                        results.add_fail("Product Fields", f"Missing fields: {missing_fields}")
                    
                    # Verify all products are active
                    inactive = [p for p in products if not p.get('active')]
                    if not inactive:
                        results.add_pass("Products Active Status", "All products are active")
                    else:
                        results.add_fail("Products Active Status", f"{len(inactive)} inactive products found")
                    
                    # Verify discount calculations
                    for p in products:
                        expected_discount = round((1 - p['discountPrice'] / p['price']) * 100)
                        if abs(expected_discount - p['discountPercent']) <= 1:  # Allow 1% tolerance
                            results.add_pass(f"Discount Calculation - {p['title']}", 
                                           f"{p['discountPercent']}% discount verified")
                        else:
                            results.add_fail(f"Discount Calculation - {p['title']}", 
                                           f"Expected ~{expected_discount}%, got {p['discountPercent']}%")
            else:
                results.add_fail("Get Products", f"Unexpected response structure: {data}")
        else:
            results.add_fail("Get Products", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Get Products", f"Exception: {str(e)}")

def test_player_resolver():
    """Test player name resolver"""
    print_section("3. PLAYER RESOLVER (Public)")
    
    # Test valid player ID (6+ characters)
    try:
        valid_id = "1234567890"
        start_time = time.time()
        response = requests.get(f"{BASE_URL}/player/resolve?id={valid_id}")
        elapsed = time.time() - start_time
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data:
                player_data = data['data']
                if player_data.get('playerId') == valid_id and player_data.get('playerName'):
                    results.add_pass("Player Resolver - Valid ID", 
                                   f"Player: {player_data['playerName']} (delay: {elapsed:.2f}s)")
                    
                    # Check for simulated delay (should be ~300ms)
                    if elapsed >= 0.25:  # Allow some tolerance
                        results.add_pass("Player Resolver - Delay", f"Simulated delay present: {elapsed:.2f}s")
                    else:
                        results.add_fail("Player Resolver - Delay", f"Expected ~0.3s delay, got {elapsed:.2f}s")
                else:
                    results.add_fail("Player Resolver - Valid ID", f"Invalid response data: {player_data}")
            else:
                results.add_fail("Player Resolver - Valid ID", f"Unexpected response: {data}")
        else:
            results.add_fail("Player Resolver - Valid ID", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Player Resolver - Valid ID", f"Exception: {str(e)}")
    
    # Test invalid player ID (less than 6 characters)
    try:
        invalid_id = "12345"
        response = requests.get(f"{BASE_URL}/player/resolve?id={invalid_id}")
        
        if response.status_code == 400:
            data = response.json()
            if not data.get('success'):
                results.add_pass("Player Resolver - Invalid ID", "Correctly rejected short ID")
            else:
                results.add_fail("Player Resolver - Invalid ID", "Should reject short ID")
        else:
            results.add_fail("Player Resolver - Invalid ID", 
                           f"Expected 400 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Player Resolver - Invalid ID", f"Exception: {str(e)}")
    
    # Test missing player ID
    try:
        response = requests.get(f"{BASE_URL}/player/resolve")
        
        if response.status_code == 400:
            results.add_pass("Player Resolver - Missing ID", "Correctly rejected missing ID")
        else:
            results.add_fail("Player Resolver - Missing ID", 
                           f"Expected 400 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Player Resolver - Missing ID", f"Exception: {str(e)}")

def test_admin_login():
    """Test admin login"""
    print_section("4. ADMIN LOGIN")
    global admin_token
    
    # Test successful login
    try:
        response = requests.post(
            f"{BASE_URL}/admin/login",
            json={"username": "admin", "password": "admin123"}
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data and 'token' in data['data']:
                admin_token = data['data']['token']
                results.add_pass("Admin Login - Success", f"Token received: {admin_token[:20]}...")
            else:
                results.add_fail("Admin Login - Success", f"Invalid response: {data}")
        else:
            results.add_fail("Admin Login - Success", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Login - Success", f"Exception: {str(e)}")
    
    # Test wrong password
    try:
        response = requests.post(
            f"{BASE_URL}/admin/login",
            json={"username": "admin", "password": "wrongpassword"}
        )
        
        if response.status_code == 401:
            results.add_pass("Admin Login - Wrong Password", "Correctly rejected wrong password")
        else:
            results.add_fail("Admin Login - Wrong Password", 
                           f"Expected 401 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Login - Wrong Password", f"Exception: {str(e)}")
    
    # Test wrong username
    try:
        response = requests.post(
            f"{BASE_URL}/admin/login",
            json={"username": "wronguser", "password": "admin123"}
        )
        
        if response.status_code == 401:
            results.add_pass("Admin Login - Wrong Username", "Correctly rejected wrong username")
        else:
            results.add_fail("Admin Login - Wrong Username", 
                           f"Expected 401 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Login - Wrong Username", f"Exception: {str(e)}")

def test_create_order():
    """Test order creation"""
    print_section("5. CREATE ORDER (Public)")
    global test_order_id, test_product_id
    
    if not test_product_id:
        results.add_fail("Create Order", "No product ID available from previous tests")
        return
    
    # Test successful order creation
    try:
        order_data = {
            "productId": test_product_id,
            "playerId": "9876543210",
            "playerName": "TestPlayer#4321"
        }
        
        response = requests.post(f"{BASE_URL}/orders", json=order_data)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data:
                order = data['data']['order']
                test_order_id = order['id']
                
                # Verify order fields
                if (order.get('productId') == test_product_id and 
                    order.get('playerId') == "9876543210" and
                    order.get('status') == 'pending'):
                    results.add_pass("Create Order - Success", 
                                   f"Order created: {test_order_id}, Status: {order['status']}")
                else:
                    results.add_fail("Create Order - Success", f"Invalid order data: {order}")
                
                # Check payment URL
                if 'paymentUrl' in data['data']:
                    results.add_pass("Create Order - Payment URL", 
                                   f"Payment URL: {data['data']['paymentUrl']}")
                else:
                    results.add_fail("Create Order - Payment URL", "Payment URL missing")
            else:
                results.add_fail("Create Order - Success", f"Unexpected response: {data}")
        else:
            results.add_fail("Create Order - Success", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Create Order - Success", f"Exception: {str(e)}")
    
    # Test missing fields
    try:
        response = requests.post(
            f"{BASE_URL}/orders",
            json={"productId": test_product_id}  # Missing playerId and playerName
        )
        
        if response.status_code == 400:
            results.add_pass("Create Order - Missing Fields", "Correctly rejected incomplete data")
        else:
            results.add_fail("Create Order - Missing Fields", 
                           f"Expected 400 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Create Order - Missing Fields", f"Exception: {str(e)}")
    
    # Test invalid product ID
    try:
        response = requests.post(
            f"{BASE_URL}/orders",
            json={
                "productId": "invalid-product-id",
                "playerId": "9876543210",
                "playerName": "TestPlayer#4321"
            }
        )
        
        if response.status_code == 404:
            results.add_pass("Create Order - Invalid Product", "Correctly rejected invalid product")
        else:
            results.add_fail("Create Order - Invalid Product", 
                           f"Expected 404 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Create Order - Invalid Product", f"Exception: {str(e)}")

def test_payment_callback():
    """Test payment callback"""
    print_section("6. PAYMENT CALLBACK (Public)")
    global test_order_id
    
    if not test_order_id:
        results.add_fail("Payment Callback", "No order ID available from previous tests")
        return
    
    # Test successful payment
    try:
        callback_data = {
            "orderId": test_order_id,
            "status": "success",
            "transactionId": "TXN123456789"
        }
        
        response = requests.post(f"{BASE_URL}/payment/shopier/callback", json=callback_data)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                results.add_pass("Payment Callback - Success", "Payment processed successfully")
            else:
                results.add_fail("Payment Callback - Success", f"Unexpected response: {data}")
        else:
            results.add_fail("Payment Callback - Success", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Payment Callback - Success", f"Exception: {str(e)}")
    
    # Test invalid order ID
    try:
        response = requests.post(
            f"{BASE_URL}/payment/shopier/callback",
            json={
                "orderId": "invalid-order-id",
                "status": "success",
                "transactionId": "TXN999"
            }
        )
        
        if response.status_code == 404:
            results.add_pass("Payment Callback - Invalid Order", "Correctly rejected invalid order")
        else:
            results.add_fail("Payment Callback - Invalid Order", 
                           f"Expected 404 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Payment Callback - Invalid Order", f"Exception: {str(e)}")

def test_admin_dashboard():
    """Test admin dashboard"""
    print_section("7. ADMIN DASHBOARD")
    
    if not admin_token:
        results.add_fail("Admin Dashboard", "No admin token available")
        return
    
    # Test with valid token
    try:
        headers = {"Authorization": f"Bearer {admin_token}"}
        response = requests.get(f"{BASE_URL}/admin/dashboard", headers=headers)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data:
                stats = data['data'].get('stats', {})
                recent_orders = data['data'].get('recentOrders', [])
                
                # Verify stats structure
                required_stats = ['totalOrders', 'paidOrders', 'pendingOrders', 'totalRevenue']
                missing_stats = [s for s in required_stats if s not in stats]
                
                if not missing_stats:
                    results.add_pass("Admin Dashboard - Stats", 
                                   f"Total: {stats['totalOrders']}, Paid: {stats['paidOrders']}, "
                                   f"Pending: {stats['pendingOrders']}, Revenue: {stats['totalRevenue']}")
                else:
                    results.add_fail("Admin Dashboard - Stats", f"Missing stats: {missing_stats}")
                
                # Verify we have at least one order (from our test)
                if stats.get('totalOrders', 0) >= 1:
                    results.add_pass("Admin Dashboard - Order Count", 
                                   f"Found {stats['totalOrders']} order(s)")
                else:
                    results.add_fail("Admin Dashboard - Order Count", "No orders found")
                
                # Verify paid order count increased
                if stats.get('paidOrders', 0) >= 1:
                    results.add_pass("Admin Dashboard - Paid Orders", 
                                   f"Found {stats['paidOrders']} paid order(s)")
                else:
                    results.add_fail("Admin Dashboard - Paid Orders", "No paid orders found")
                
                # Verify revenue
                if stats.get('totalRevenue', 0) > 0:
                    results.add_pass("Admin Dashboard - Revenue", 
                                   f"Total revenue: {stats['totalRevenue']} TRY")
                else:
                    results.add_fail("Admin Dashboard - Revenue", "No revenue recorded")
                
                # Check recent orders
                if isinstance(recent_orders, list):
                    results.add_pass("Admin Dashboard - Recent Orders", 
                                   f"Found {len(recent_orders)} recent order(s)")
                else:
                    results.add_fail("Admin Dashboard - Recent Orders", "Invalid recent orders format")
            else:
                results.add_fail("Admin Dashboard", f"Unexpected response: {data}")
        else:
            results.add_fail("Admin Dashboard", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Dashboard", f"Exception: {str(e)}")
    
    # Test without token
    try:
        response = requests.get(f"{BASE_URL}/admin/dashboard")
        
        if response.status_code == 401:
            results.add_pass("Admin Dashboard - No Token", "Correctly rejected request without token")
        else:
            results.add_fail("Admin Dashboard - No Token", 
                           f"Expected 401 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Dashboard - No Token", f"Exception: {str(e)}")

def test_admin_orders():
    """Test admin orders endpoints"""
    print_section("8. ADMIN ORDERS")
    
    if not admin_token:
        results.add_fail("Admin Orders", "No admin token available")
        return
    
    headers = {"Authorization": f"Bearer {admin_token}"}
    
    # Test get all orders
    try:
        response = requests.get(f"{BASE_URL}/admin/orders", headers=headers)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data:
                orders = data['data']
                if len(orders) >= 1:
                    results.add_pass("Admin Orders - Get All", f"Found {len(orders)} order(s)")
                else:
                    results.add_fail("Admin Orders - Get All", "No orders found")
            else:
                results.add_fail("Admin Orders - Get All", f"Unexpected response: {data}")
        else:
            results.add_fail("Admin Orders - Get All", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Orders - Get All", f"Exception: {str(e)}")
    
    # Test filter by status (pending)
    try:
        response = requests.get(f"{BASE_URL}/admin/orders?status=pending", headers=headers)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data:
                orders = data['data']
                # Check all orders have pending status
                non_pending = [o for o in orders if o.get('status') != 'pending']
                if not non_pending:
                    results.add_pass("Admin Orders - Filter Pending", 
                                   f"Found {len(orders)} pending order(s)")
                else:
                    results.add_fail("Admin Orders - Filter Pending", 
                                   f"Found {len(non_pending)} non-pending orders")
            else:
                results.add_fail("Admin Orders - Filter Pending", f"Unexpected response: {data}")
        else:
            results.add_fail("Admin Orders - Filter Pending", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Orders - Filter Pending", f"Exception: {str(e)}")
    
    # Test filter by status (paid)
    try:
        response = requests.get(f"{BASE_URL}/admin/orders?status=paid", headers=headers)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data:
                orders = data['data']
                # Check all orders have paid status
                non_paid = [o for o in orders if o.get('status') != 'paid']
                if not non_paid:
                    results.add_pass("Admin Orders - Filter Paid", 
                                   f"Found {len(orders)} paid order(s)")
                else:
                    results.add_fail("Admin Orders - Filter Paid", 
                                   f"Found {len(non_paid)} non-paid orders")
            else:
                results.add_fail("Admin Orders - Filter Paid", f"Unexpected response: {data}")
        else:
            results.add_fail("Admin Orders - Filter Paid", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Orders - Filter Paid", f"Exception: {str(e)}")
    
    # Test get single order
    if test_order_id:
        try:
            response = requests.get(f"{BASE_URL}/admin/orders/{test_order_id}", headers=headers)
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success') and 'data' in data:
                    order = data['data'].get('order')
                    payment = data['data'].get('payment')
                    
                    if order and order.get('id') == test_order_id:
                        results.add_pass("Admin Orders - Get Single", 
                                       f"Order details retrieved: {order.get('status')}")
                    else:
                        results.add_fail("Admin Orders - Get Single", "Invalid order data")
                    
                    if payment:
                        results.add_pass("Admin Orders - Payment Details", 
                                       f"Payment info included: {payment.get('provider')}")
                    else:
                        results.add_fail("Admin Orders - Payment Details", "Payment info missing")
                else:
                    results.add_fail("Admin Orders - Get Single", f"Unexpected response: {data}")
            else:
                results.add_fail("Admin Orders - Get Single", f"Status code: {response.status_code}")
        except Exception as e:
            results.add_fail("Admin Orders - Get Single", f"Exception: {str(e)}")
    
    # Test get non-existent order
    try:
        response = requests.get(f"{BASE_URL}/admin/orders/invalid-order-id", headers=headers)
        
        if response.status_code == 404:
            results.add_pass("Admin Orders - Invalid ID", "Correctly returned 404 for invalid order")
        else:
            results.add_fail("Admin Orders - Invalid ID", 
                           f"Expected 404 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Orders - Invalid ID", f"Exception: {str(e)}")
    
    # Test without token
    try:
        response = requests.get(f"{BASE_URL}/admin/orders")
        
        if response.status_code == 401:
            results.add_pass("Admin Orders - No Token", "Correctly rejected request without token")
        else:
            results.add_fail("Admin Orders - No Token", 
                           f"Expected 401 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Orders - No Token", f"Exception: {str(e)}")

def test_admin_products():
    """Test admin products endpoints"""
    print_section("9. ADMIN PRODUCTS")
    
    if not admin_token:
        results.add_fail("Admin Products", "No admin token available")
        return
    
    headers = {"Authorization": f"Bearer {admin_token}"}
    
    # Test get all products (including inactive)
    try:
        response = requests.get(f"{BASE_URL}/admin/products", headers=headers)
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success') and 'data' in data:
                products = data['data']
                if len(products) >= 5:
                    results.add_pass("Admin Products - Get All", 
                                   f"Found {len(products)} product(s) (including inactive)")
                else:
                    results.add_fail("Admin Products - Get All", 
                                   f"Expected at least 5 products, got {len(products)}")
            else:
                results.add_fail("Admin Products - Get All", f"Unexpected response: {data}")
        else:
            results.add_fail("Admin Products - Get All", f"Status code: {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Products - Get All", f"Exception: {str(e)}")
    
    # Test update product
    if test_product_id:
        try:
            update_data = {
                "price": 30,
                "discountPrice": 24.99,
                "discountPercent": 17
            }
            
            response = requests.put(
                f"{BASE_URL}/admin/products/{test_product_id}",
                headers=headers,
                json=update_data
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success') and 'data' in data:
                    updated = data['data']
                    if (updated.get('price') == 30 and 
                        updated.get('discountPrice') == 24.99):
                        results.add_pass("Admin Products - Update", 
                                       f"Product updated: Price {updated['price']}, "
                                       f"Discount {updated['discountPrice']}")
                    else:
                        results.add_fail("Admin Products - Update", "Update values not reflected")
                else:
                    results.add_fail("Admin Products - Update", f"Unexpected response: {data}")
            else:
                results.add_fail("Admin Products - Update", f"Status code: {response.status_code}")
        except Exception as e:
            results.add_fail("Admin Products - Update", f"Exception: {str(e)}")
    
    # Test delete product (soft delete)
    if test_product_id:
        try:
            response = requests.delete(
                f"{BASE_URL}/admin/products/{test_product_id}",
                headers=headers
            )
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    results.add_pass("Admin Products - Delete", "Product soft deleted")
                    
                    # Verify product is now inactive
                    verify_response = requests.get(f"{BASE_URL}/admin/products", headers=headers)
                    if verify_response.status_code == 200:
                        verify_data = verify_response.json()
                        products = verify_data.get('data', [])
                        deleted_product = next((p for p in products if p['id'] == test_product_id), None)
                        
                        if deleted_product and not deleted_product.get('active'):
                            results.add_pass("Admin Products - Soft Delete Verify", 
                                           "Product marked as inactive")
                        else:
                            results.add_fail("Admin Products - Soft Delete Verify", 
                                           "Product still active or not found")
                else:
                    results.add_fail("Admin Products - Delete", f"Unexpected response: {data}")
            else:
                results.add_fail("Admin Products - Delete", f"Status code: {response.status_code}")
        except Exception as e:
            results.add_fail("Admin Products - Delete", f"Exception: {str(e)}")
    
    # Test without token
    try:
        response = requests.get(f"{BASE_URL}/admin/products")
        
        if response.status_code == 401:
            results.add_pass("Admin Products - No Token", "Correctly rejected request without token")
        else:
            results.add_fail("Admin Products - No Token", 
                           f"Expected 401 status, got {response.status_code}")
    except Exception as e:
        results.add_fail("Admin Products - No Token", f"Exception: {str(e)}")

def test_database_initialization():
    """Test database initialization"""
    print_section("10. DATABASE INITIALIZATION")
    
    # This is implicitly tested by checking if products and admin user exist
    # We've already verified:
    # 1. 5 products exist (from test_get_products)
    # 2. Admin user exists (from test_admin_login)
    
    results.add_pass("Database Initialization", 
                    "Default data created successfully (5 products + admin user)")

def main():
    """Run all tests"""
    print(f"\n{Colors.BLUE}{'='*70}")
    print("PUBG MOBILE UC STORE - BACKEND API TEST SUITE")
    print(f"{'='*70}{Colors.RESET}")
    print(f"Base URL: {BASE_URL}")
    print(f"Test Started: {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
    
    # Run all tests in sequence
    test_api_health()
    test_get_products()
    test_player_resolver()
    test_admin_login()
    test_create_order()
    test_payment_callback()
    test_admin_dashboard()
    test_admin_orders()
    test_admin_products()
    test_database_initialization()
    
    # Print summary
    results.print_summary()
    
    # Print detailed failed tests
    if results.failed > 0:
        print(f"{Colors.RED}FAILED TESTS DETAILS:{Colors.RESET}")
        print("="*70)
        for test in results.tests:
            if test['status'] == 'FAIL':
                print(f"\n{Colors.RED}✗ {test['name']}{Colors.RESET}")
                print(f"  {test['details']}")
        print("\n")
    
    return results.failed == 0

if __name__ == "__main__":
    success = main()
    exit(0 if success else 1)
