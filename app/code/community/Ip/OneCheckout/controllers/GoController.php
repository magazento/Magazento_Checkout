<?php
$OnepageController = BP . DS . 'app' . DS . 'code' . DS . 'core' . DS . 'Mage' . DS . 'Checkout' . DS . 'controllers' . DS . 'OnepageController.php';
require_once $OnepageController;
class Ip_OneCheckout_GoController extends Mage_Checkout_OnepageController
{
    protected function _getReviewHtml( )
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load( 'checkout_go_review' );
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }
    protected function getCheckout( )
    {
        return $this->getOnepage()->getCheckout();
    }
    private function getQuote( )
    {
        return $this->getCheckout()->getQuote();
    }
    private function setDefaultCountryId( )
    {
        $defaultCountry = Mage::getStoreConfig( 'general/country/default' );
        $this->getQuote()->getShippingAddress()->setCountryId( $defaultCountry )->save();
    }
    
    public function indexAction( )
    {
        if ( !Mage::helper( 'onecheckout' )->getConfig( 'general/active' ) ) {
            $this->_redirect( 'checkout/onepage' );
            return;
        } //!Mage::helper( 'onecheckout' )->getConfig( 'general/active' )
        
        
        Mage::getSingleton( 'checkout/session' )->setRBR( true );
        $quote = $this->getOnepage()->getQuote();
        if ( !$quote->hasItems() || $quote->getHasError() ) {
            $this->_redirect( 'checkout/cart' );
            return;
        } //!$quote->hasItems() || $quote->getHasError()
        
        
        if ( !$quote->validateMinimumAmount() ) {
            $error = Mage::getStoreConfig( 'sales/minimum_order/error_message' );
            Mage::getSingleton( 'checkout/session' )->addError( $error );
            $this->_redirect( 'checkout/cart' );
            return;
        } //!$quote->validateMinimumAmount()
        
        
        if ( !count( Mage::getSingleton( 'customer/session' )->getCustomer()->getAddresses() ) ) {
            $this->setDefaultCountryId();
        } //!count( Mage::getSingleton( 'customer/session' )->getCustomer()->getAddresses() )
        
        
        $this->getQuote()->getShippingAddress()->setCollectShippingRates( true );
        Mage::getSingleton( 'checkout/session' )->setCartWasUpdated( false );
        Mage::getSingleton( 'customer/session' )->setBeforeAuthUrl( Mage::getUrl( '*/*/*', array(
             '_secure' => true 
        ) ) );
        $this->getOnepage()->initCheckout();
        if ( !Mage::getSingleton( 'customer/session' )->isLoggedIn() ) {
            $this->getOnepage()->saveCheckoutMethod( Mage_Sales_Model_Quote::CHECKOUT_METHOD_GUEST );
        } //!Mage::getSingleton( 'customer/session' )->isLoggedIn()
        
        
        $this->loadLayout();
        $this->_initLayoutMessages( 'customer/session' );
        $this->renderLayout();
    }
    
    public function loginPostAction( )
    {
        if ( Mage::getSingleton( 'customer/session' )->isLoggedIn() ) {
            $this->_redirect( '*/*/' );
            return;
        } //Mage::getSingleton( 'customer/session' )->isLoggedIn()
        $session  = Mage::getSingleton( 'customer/session' );
        $message  = '';
        $res = array( );
        if ( $this->getRequest()->isPost() ) {
            $login = $this->getRequest()->getPost();
            if ( !empty( $login['username'] ) && !empty( $login['password'] ) ) {
                try {
                    $session->login( $login['username'], $login['password'] );
                    if ( $session->getCustomer()->getIsJustConfirmed() ) {
                        $this->_welcomeCustomer( $session->getCustomer(), true );
                    } //$session->getCustomer()->getIsJustConfirmed()
                }
                catch ( Mage_Core_Exception $e ) {
                    switch ( $e->getCode() ) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $message = Mage::helper( 'customer' )->__( 'This account is not confirmed. <a href="%s">Resend confirmation email.</a> ', Mage::helper( 'customer' )->getEmailConfirmationUrl( $login['username'] ) );
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    } //$e->getCode()
                    $session->setUsername( $login['username'] );
                }
                catch ( Exception $e ) {
                    $message = $e->getMessage();
                }
            } //!empty( $login['username'] ) && !empty( $login['password'] )
            else {
                $message = $this->__( 'Login and password are required' );
            }
        } //$this->getRequest()->isPost()
        if ( $message ) {
            $res['error'] = $message;
        } //$message
        else {
            $res['redirect'] = 1;
        }
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
    }
    
    public function forgotpassPostAction( )
    {
        $email = $this->getRequest()->getPost( 'email' );
        if ( $email ) {
            if ( !Zend_Validate::is( $email, 'EmailAddress' ) ) {
                $message = $this->__( 'Invalid email address.' );
            } //!Zend_Validate::is( $email, 'EmailAddress' )
            else {
                $customer = Mage::getModel( 'customer/customer' )->setWebsiteId( Mage::app()->getStore()->getWebsiteId() )->loadByEmail( $email );
                if ( $customer->getId() ) {
                    try {
                        $newPassword = $customer->generatePassword();
                        $customer->changePassword( $newPassword, false );
                        $customer->sendPasswordReminderEmail();
                        $message = $this->__( 'A new password has been sent.' );
                    }
                    catch ( Exception $e ) {
                        $message = $e->getMessage();
                    }
                } //$customer->getId()
                else {
                    $message = $this->__( 'This email address was not found in our records.' );
                }
            }
        } //$email
        else {
            $message = $this->__( 'Please enter your email.' );
        }
        $res['error'] = $message;
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
    }
    
    public function UpdateTotalsAction( )
    {
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        $res = array( );
        try {
            $res['review'] = $this->_getReviewHtml();
        }
        catch ( Exception $e ) {
            $res['success']        = false;
            $res['error']          = true;
            $res['error_messages'] = $e->getMessage();
        }
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
    }
//    public function switchMethodAction( )
//    {
//        if ( $this->_expireAjax() ) {
//            return;
//        } //$this->_expireAjax()
//        $method = $this->getRequest()->getPost( 'method' );
//        if ( $this->getRequest()->isPost() && $method )
//            $this->getOnepage()->saveCheckoutMethod( $method );
//        
//        $res['error'] = $method;
//        $this->getResponse()->setBody( Zend_Json::encode( $res ) );        
//    }
    
    public function UpdateTotalsPaymentAction( )
    {
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        $res = array( );
        try {
            $res['payment'] = $this->_getPaymentMethodsHtml();
        }
        catch ( Exception $e ) {
            $res['success']        = false;
            $res['error']          = true;
            $res['error_messages'] = $e->getMessage();
        }
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
    }
    
    public function saveBillingAction( )
    {
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        $res = array( );
        $data     = $this->getRequest()->getPost();
        if ( $data ) {
            
            if ( $data['usebilling'] == 'false' ) {
                Mage::getSingleton( 'checkout/session' )->setData( 'use_for_shipping', false );
                try {
                    $this->getQuote()->getBillingAddress()->setCountryId( $data['country_id'] )->setPostcode( $data['postcode'] )->setRegionId( $data['region_id'] )->save();
                    $this->getQuote()->getShippingAddress()->setCountryId( $data['country_id'] )->setPostcode( $data['postcode'] )->setRegionId( $data['region_id'] )->save();
                    $this->getQuote()->getShippingAddress()->setCollectShippingRates( true );
                    $this->getQuote()->collectTotals()->save();
                    $res['shippingMethod'] = $this->_getShippingMethodsHtml();
                    $res['payment']        = $this->_getPaymentMethodsHtml();
                }
                catch ( Exception $e ) {
                    $res['success']        = false;
                    $res['error']          = true;
                    $res['error_messages'] = $e->getMessage();
                }
            } //$data['usebilling'] == 'true'
            
            if ( $data['usebilling'] == 'true' ) {
                Mage::getSingleton( 'checkout/session' )->setData( 'use_for_shipping', true );
                try {
                    $this->getQuote()->getBillingAddress()->setCountryId( $data['country_id'] )->setPostcode( $data['postcode'] )->setRegionId( $data['region_id'] )->save();
                    $res['payment'] = $this->_getPaymentMethodsHtml();
                }
                catch ( Exception $e ) {
                    $res['success']        = false;
                    $res['error']          = true;
                    $res['error_messages'] = $e->getMessage();
                }
            } //$data['usebilling'] == 'false'
            

            $res['usebilling'] = $data['usebilling'];
            $res['call']       = 'true';
            $this->getResponse()->setBody( Zend_Json::encode( $res ) );
        } //$data
    }
    
    public function SaveTotalsAction( )
    {
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        $res = array( );
        $billdata = $this->getRequest()->getPost();
        if ( $billdata ) {
            $data = $this->getRequest()->getPost( 'billing', array( ) );
            if ( !$data['use_for_shipping'] ) {
                Mage::getSingleton( 'checkout/session' )->setData( 'use_for_shipping', false );
                $customerAddressId = $this->getRequest()->getPost( 'billing_address_id', false );
                if ( isset( $data['email'] ) ) {
                    $data['email'] = trim( $data['email'] );
                } //isset( $data['email'] )
                $this->getOnepage()->saveBilling( $data, $customerAddressId );
                $customerAddressId2 = $this->getRequest()->getPost( 'shipping_address_id', false );
                $this->getOnepage()->saveShipping( $data, $customerAddressId2 );
                $this->getQuote()->collectTotals()->save();
            } //!$data['use_for_shipping']
            else {
                $customerAddressId = $this->getRequest()->getPost( 'billing_address_id', false );
                if ( isset( $data['email'] ) ) {
                    $data['email'] = trim( $data['email'] );
                } //isset( $data['email'] )
                $this->getOnepage()->saveBilling( $data, $customerAddressId );
                if ( $data2 = $this->getRequest()->getPost( 'shipping', array( ) ) ) {
                    $customerAddressId2 = $this->getRequest()->getPost( 'shipping_address_id', false );
                    $this->getOnepage()->saveShipping( $data2, $customerAddressId2 );
                } //$data2 = $this->getRequest()->getPost( 'shipping', array( ) )
                $this->getQuote()->collectTotals()->save();
            }
        } //$billdata
        $res['success'] = true;
        $res['error']   = false;
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
        return;
    }
    
    public function saveShippingAction( )
    {
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        Mage::getSingleton( 'checkout/session' )->setData( 'use_for_shipping', true );
        $res = array( );
        $data     = $this->getRequest()->getPost();
        if ( $data ) {
            try {
                $this->getQuote()->getShippingAddress()->setCountryId( $data['country_id'] )->setPostcode( $data['postcode'] )->setRegionId( $data['region_id'] )->save();
                $this->getQuote()->getShippingAddress()->setCollectShippingRates( true );
                $this->getQuote()->collectTotals()->save();
                $res['shippingMethod'] = $this->_getShippingMethodsHtml();
            }
            catch ( Exception $e ) {
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = $e->getMessage();
            }
        } //$data
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
    }
    
    public function saveShippingMethodAction( )
    {
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        $res = array( );
        $data     = $this->getRequest()->getPost();
        if ( $data ) {
            try {
                $return = $this->getOnepage()->saveShippingMethod( $data['shipping_method'] );
                if ( !$return ) {
                    Mage::dispatchEvent( 'checkout_controller_onepage_save_shipping_method', array(
                         'request' => $this->getRequest(),
                        'quote' => $this->getOnepage()->getQuote() 
                    ) );
                } //!$return
                $res['payment'] = $this->_getPaymentMethodsHtml();
                $res['review']  = $this->_getReviewHtml();
            }
            catch ( Exception $e ) {
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = $e->getMessage();
            }
        } //$data
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
    }
    
    public function savePaymentAction( )
    {
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        $res = array( );
        $data     = $this->getRequest()->getPost();
        if ( $data ) {
            try {
                $this->getQuote()->getBillingAddress()->setPaymentMethod( $data['method'] )->save();
                $this->getQuote()->getPayment()->setMethod( $data['method'] )->save();
                $this->getQuote()->collectTotals();
                Mage::getSingleton( 'checkout/session' )->setRBR( true );
                if ( $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl() ) {
                    Mage::getSingleton( 'checkout/session' )->setRBR( false );
                } //$this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl()
                $res['test']   = $this->getQuote()->getPayment()->getMethod();
                $res['review'] = $this->_getReviewHtml();
            }
            catch ( Exception $e ) {
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = $e->getMessage();
            }
        } //$data
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
    }
    
    public function couponPostAction( )
    {
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        $couponCode = (string) $this->getRequest()->getParam( 'coupon_code' );
        if ( $remov = $this->getRequest()->getParam('remove') == 1 ) {
            $couponCode = '';
        } //$remov = $this->getRequest()->getParam( 'remove' ) == 1
        $oldCouponCode = Mage::helper( 'onecheckout/go' )->_getQuote()->getCouponCode();
        if ( !strlen( $couponCode ) && !strlen( $oldCouponCode ) ) {
            $res['enabled'] = false;
            return;
        } //!strlen( $couponCode ) && !strlen( $oldCouponCode )
        try {
            Mage::helper( 'onecheckout/go' )->_getQuote()->getShippingAddress()->setCollectShippingRates( true );
            Mage::helper( 'onecheckout/go' )->_getQuote()->setCouponCode( strlen( $couponCode ) ? $couponCode : '' )->collectTotals()->save();
            if ( $couponCode ) {
                if ( $couponCode == Mage::helper( 'onecheckout/go' )->_getQuote()->getCouponCode() ) {
                    $res['success'] = $this->__( 'Coupon "%s"', Mage::helper( 'core' )->htmlEscape( $couponCode ) );
                } //$couponCode == Mage::helper( 'onecheckout/go' )->_getQuote()->getCouponCode()
                else {
                    $res['error'] = $this->__( '"%s" -  not valid.', Mage::helper( 'core' )->htmlEscape( $couponCode ) );
                }
            } //$couponCode
            else {
                $res['error'] = $this->__( 'Coupon code was canceled' );
            }
        }
        catch ( Mage_Core_Exception $e ) {
            $res['error'] = $e->getMessage();
        }
        catch ( Exception $e ) {
            $res['error'] = $this->__( 'Can not apply coupon' );
        }
        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
        return;
    }
    
    public function saveOrderAction( )
    {
        
        $storeId = Mage::app()->getStore()->getId();
        $websitemodel = Mage::getModel('core/website')->load($storeId); //website model
        $websiteId = $websitemodel->getId();
        
        
        
        if ( $this->_expireAjax() ) {
            return;
        } //$this->_expireAjax()
        if ( $this->getRequest()->isPost() ) {
            
            $res = array( );
            Mage::dispatchEvent( 'checkout_controller_onecheckout_save_order_after', array(
                 'request' => $this->getRequest(),
                'quote' => $this->getOnepage()->getQuote() 
            ) );
            $billingPostData = $this->getRequest()->getPost( 'billing', array( ) );
            if ( Mage::getVersion() >= '1.4.0.1' && Mage::getVersion() < '1.4.2.0' ) {
                $billingData = $this->_filterPostData( $billingPostData );
            } //Mage::getVersion() >= '1.4.0.1' && Mage::getVersion() < '1.4.2.0'
            else {
                $billingData = $billingPostData;
            }
            
            if ( !$email = Mage::getSingleton( 'customer/session' )->getCustomer()->getEmail() ) {
                $email=  $billingData['email'];
            } //!$email = Mage::getSingleton( 'customer/session' )->getCustomer()->getEmail()     
            
            
            if ($billingData['register']) {
                $Customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail($email);
                if ($Customer->getId()) {
                    $res['success']        = false;
                    $res['error']          = true;
                    $res['error_messages'] = $this->__('This email has been already taken.');
                    $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                    return; 
                } else {
                    $this->getOnepage()->saveCheckoutMethod( Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER );
                }
            } 
            
            
            $customerAddressId = $this->getRequest()->getPost( 'billing_address_id', false );
            if ( isset( $billingData['email'] ) ) {
                $billingData['email'] = trim( $billingData['email'] );
            } //isset( $billingData['email'] )
            if ( $this->getRequest()->getParam( 'is_subscribed', false ) ) {
                $status = Mage::getModel( 'newsletter/subscriber' )->subscribe( $email );
                if ( $status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE ) {
                    $res['email'] = ( $this->__( 'Confirmation request has been sent' ) );
                } //$status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE
            } //$this->getRequest()->getParam( 'is_subscribed', false )
            $resBilling = $this->getOnepage()->saveBilling( $billingData, $customerAddressId );
            if ( isset( $resBilling['error'] ) ) {
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = 'Billing Error: ' . $resBilling['message'];
                $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                return;
            } //isset( $resBilling['error'] )
            if ( isset( $billingData['use_for_shipping'] ) && $billingData['use_for_shipping'] == 1 ) {
                $shippingData      = $this->getRequest()->getPost( 'shipping', array( ) );
                $customerAddressId = $this->getRequest()->getPost( 'shipping_address_id', false );
                $resShipping  = $this->getOnepage()->saveShipping( $shippingData, $customerAddressId );
                if ( isset( $resShipping['error'] ) ) {
                    $res['success']        = false;
                    $res['error']          = true;
                    $res['error_messages'] = 'Shipping Error: ' . $resShipping['message'];
                    $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                    return;
                } //isset( $resShipping['error'] )
            } //isset( $billingData['use_for_shipping'] ) && $billingData['use_for_shipping'] == 1
            else {
                $resShipping = $this->getOnepage()->saveShipping( $billingData, $customerAddressId );
                if ( isset( $resShipping['error'] ) ) {
                    $res['success']        = false;
                    $res['error']          = true;
                    $res['error_messages'] = 'Shipping Error: ' . $resShipping['message'];
                    $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                    return;
                } //isset( $resShipping['error'] )
            }
            $shippingMethodData     = $this->getRequest()->getPost( 'shipping_method', '' );
            $resShippingMethod = $this->getOnepage()->saveShippingMethod( $shippingMethodData, '' );
            try {
                $paymentData     = $this->getRequest()->getPost( 'payment', array( ) );
                $resPayment = $this->getOnepage()->savePayment( $paymentData );
                if ( $this->getRequest()->getPost( 'payment', false ) && Mage::getSingleton( 'checkout/session' )->getRBR() ) {
                    $data = $this->getRequest()->getPost( 'payment', false );
                    $this->getOnepage()->getQuote()->getPayment()->importData( $data );
                    $this->getOnepage()->saveOrder();
                    $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
                } //$this->getRequest()->getPost( 'payment', false ) && Mage::getSingleton( 'checkout/session' )->getRBR()
                if ( isset( $resPayment['error'] ) ) {
                    $res['success']        = false;
                    $res['error']          = true;
                    $res['error_messages'] = $this->__( 'Your order cannot be completed at this time as there is no payment methods available for it.');
                    $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                    return;
                } //isset( $resPayment['error'] )
            }
            catch ( Mage_Payment_Exception $e ) {
                if ( $e->getFields() ) {
                    $res['fields'] = $e->getFields();
                } //$e->getFields()
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = 'Payment Method Error:' . $e->getMessage();
                $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                return;
            }
            catch ( Mage_Core_Exception $e ) {
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = 'Core Exception: ' . $e->getMessage();
                $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                return;
            }
            catch ( Exception $e ) {
                Mage::logException( $e );
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = 'Exception: ' . $this->__( 'Unable to set Payment Method.' );
                $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                return;
            }
            try {
                if ( $requiredAgreements = Mage::helper( 'checkout' )->getRequiredAgreementIds() ) {
                    $postedAgreements = array_keys( $this->getRequest()->getPost( 'agreement', array( ) ) );
                    if ( $diff = array_diff( $requiredAgreements, $postedAgreements ) ) {
                        $res['success']        = false;
                        $res['error']          = true;
                        $res['error_messages'] = $this->__( 'Please agree to all Terms and Conditions before placing the order.' );
                        $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                        return;
                    } //$diff = array_diff( $requiredAgreements, $postedAgreements )
                } //$requiredAgreements = Mage::helper( 'checkout' )->getRequiredAgreementIds()
                if ( $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl() ) {
                    $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
                } //$this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl()
                $res['success'] = true;
                $res['error']   = false;
            }
            catch ( Mage_Core_Exception $e ) {
                Mage::logException( $e );
                Mage::helper( 'checkout' )->sendPaymentFailedEmail( $this->getOnepage()->getQuote(), $e->getMessage() );
                $this->getOnepage()->getQuote()->save();
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = $e->getMessage();
                $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                return;
            }
            catch ( Exception $e ) {
                Mage::logException( $e );
                Mage::helper( 'checkout' )->sendPaymentFailedEmail( $this->getOnepage()->getQuote(), $e->getMessage() );
                $this->getOnepage()->getQuote()->save();
                $res['success']        = false;
                $res['error']          = true;
                $res['error_messages'] = 'Exception: ' . $this->__( 'There was an error processing your order. Please contact us or try again later.' );
                $this->getResponse()->setBody( Zend_Json::encode( $res ) );
                return;
            }
            if ( $redirectUrl ) {
                $res['redirect'] = $redirectUrl;
            } //$redirectUrl
            $this->getOnepage()->getQuote()->save();
            $this->getCheckout()->unsetData( 'use_for_shipping' );
            $this->getResponse()->setBody( Zend_Json::encode( $res ) );
        } //$this->getRequest()->isPost()
    }
}