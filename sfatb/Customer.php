<?php
	/**
	 * This file contains the customer class
	 * @package SFATB
	 */
	/**
	 * Customers/companies stored in the system
	 *
	 * Contains getters of customer related information, status
	 * update methods and balance related functionality.
	 * @package SFATB
	 */
	class Customer extends ActiveRecord {
		/**
		 * The group which this customer belongs to
		 * @var Group
		 */
		private $group;
		/**
		 * Contains company address
		 * @var Address
		 */
		private $address;
		/**
		 * All customer contacts are stored here
		 * @var array
		 */
		private $contacts;
		/**
		 * Generated customer invoices are stored here
		 * @var array
		 */
		private $invoices;
		/**
		 * All customer notification settings are stored here
		 * @var array
		 */
		private $notifications;
		
		/**
		 * Constructor initializes the attributes and loads object fields
		 * 
		 * Specifies the database column names which become object attributes.
		 * Specifies the database table name. Loads the object by passed
		 * attribute values or ID from database.
		 * @param array associative array of attribute names and values OR
		 * ID of the record stored in DB
		 */
		public function __construct( $attributes = array() ) {
			$this->fields = array( 'id', 'group_id', 'address_id', 'created_at', 'phone', 'fax', 'tax_num', 'chamber_of_commerce', 'bank_name', 'account_number', 'iban', 'swift_code', 'max_credit', 'credit_currency', 'status', 'confirmation_code', 'webservice_username', 'webservice_password', 'salt', 'webservice_ip', 'force_insurance', 'default_sender_country', 'default_receiver_country' );
			$this->table = 'customers';
			$this->load( $attributes );
		}
		
		/**
		 * Validates the Customer object
		 * 
		 * Create errors on missing or duplicate fields.
		 * @return boolean whether object has errors or not
		 */
		public function validate() {
			if( empty( $this->attributes['group_id'] ) )
				$this->addError( "Group must be selected." );
			if( empty( $this->attributes['address_id'] ) )
				$this->addError( "Selected address is invalid." );
			if( empty( $this->attributes['phone'] ) )
				$this->addError( "Phone cannot be empty." );
			if( empty( $this->attributes['tax_num'] ) )
				$this->addError( "Tax number cannot be empty." );
			elseif( $this->find( array( 'conditions' => array( 'tax_num = ? and id != ?', $this->tax_num, $this->getId() ) ) ) )
				$this->addError( "Tax number has already been registered. Please use another one." );
			if( $this->chamber_of_commerce && $this->find( array( 'conditions' => array( 'chamber_of_commerce = ? and id != ?', $this->chamber_of_commerce, $this->getId() ) ) ) )
				$this->addError( "Chamber of commerce number has already been registered. Please use another one." );
			if( !in_array( $this->attributes['status'], array( 'unconfirmed', 'pending', 'approved', 'suspended' ) ) )
				$this->addError( "Invalid status selected." );
			if( sizeof( $this->getErrors() ) > 0 )
				return false;
			return true;
		}
		
		/**
		 * Destroys the customer object and all related contacts.
		 * 
		 * @return boolean the result of the destruction operation
		 */
		public function destroy() {
			foreach( $this->getContacts() as $contact )
				$contact->destroy();
			$this->getAddress()->destroy();
			foreach( $this->getNotifications() as $notification )
				$notification->destroy();
			return parent::destroy();
		}
		
		/**
		 * Create random confirmation code and hash the password with salt before creating the record
		 * 
		 * @return boolean true
		 */
		public function before_create() {
			# set confirmation code
			do {
				$code = substr( sha1( $this->attributes['email'].rand(0,999).$this->attributes['phone'].time().$this->attributes['address_id'] ), rand( 0, 22 ), 18 );
			} while( $this->find( array( 'conditions' => array( 'confirmation_code' => $code ) ) ) );
			$this->attributes['confirmation_code'] = $code;
			# hash the password
			if( !$this->salt )
				$this->setPassword( $this->webservice_password );
			# auto-create webservice username
			if( !$this->webservice_username ) {
				$this->attributes['webservice_username'] = 'customer'.$this->tax_num;
			}
			# handle null values
			$this->attributes['force_insurance'] = intval( $this->force_insurance );
			return true;
		}
		
		/**
		 * Creates random salt and hash the provided password
		 * 
		 * @param string the plain text password
		 */
		public function setPassword( $pass ) {
			if( empty( $pass ) )
				return;
			# salt password
			$salt = '';
			for( $i = 0; $i < 16; $i++ )
				$salt .= chr(rand(32,126));
			$this->attributes['salt'] = $salt;
			$this->attributes['webservice_password'] = sha1( $pass . $salt );
		}
		
		/**
		 * Authenticates the current Customer object against the webservice
		 * 
		 * Checks the username and password match against database
		 * and possibly loads the matched record.
		 * @return boolean whether there is or isn't such customer
		 */
		public function auth() {
			$customers = $this->find( array( 'conditions' => array( 'webservice_username' => $this->webservice_username ) ) );
			foreach( $customers as $customer ) {
				if( sha1( $this->webservice_password . $customer->salt ) == $customer->webservice_password ) {
					$this->load( $customer->attributes );
					return true;
				}
			}
			return false;
		}
		
		/**
		 * Returns the Group object associated with this customer
		 * 
		 * Stores internally on first access to allow caching for
		 * faster consequtive lookups. Instantiates the Group object
		 * with all data from DB.
		 * @return Group the group object
		 */
		public function getGroup() {
			if( !isset( $this->group ) )
				$this->group = new Group( $this->attributes['group_id'] );
			return $this->group;
		}
		
		/**
		 * Returns the Address object associated with this customer
		 * 
		 * Stores internally on first access to allow caching for
		 * faster consequtive lookups. Instantiates the Address object
		 * with all data from DB.
		 * @return Address the address object
		 */
		public function getAddress() {
			if( !isset( $this->address ) )
				$this->address = new Address( $this->attributes['address_id'] );
			return $this->address;
		}
		
		/**
		 * Returns the Contact objects associated with this customer
		 * 
		 * Stores internally on first access to allow caching for
		 * faster consequtive lookups. Instantiates the Contact objects
		 * with all data from DB.
		 * @return array the contact objects
		 */
		public function getContacts() {
			if( !isset( $this->contacts ) ) {
				$c = new Contact();
				$this->contacts = $c->find( array( 'conditions' => array( 'customer_id' => $this->getId() ) ) );
			}
			return $this->contacts;
		}
		
		/**
		 * Returns the Notification objects associated with this customer
		 * 
		 * Stores internally on first access to allow caching for
		 * faster consequtive lookups. Instantiates the Notification objects
		 * with all data from DB.
		 * @return array the notification objects
		 */
		public function getNotifications() {
			if( !isset( $this->notifications ) ) {
				$cn = new CustomerNotification();
				$this->notifications = $cn->find( array( 'conditions' => array( 'customer_id' => $this->getId() ) ) );
			}
			return $this->notifications;
		}
		
		/**
		 * Returns the Notification object associated with this customer own settings
		 * 
		 * @return array the notification object
		 */
		public function getOwnNotifications() {
			foreach( $this->getNotifications() as $cn )
				if( $cn->type == 'own' )
					return $cn;
			return new CustomerNotification( array( 'customer_id' => $this->getId(), 'type' => 'own' ) );
		}
		
		/**
		 * Returns the Notification object associated with this customer sender settings
		 * 
		 * @return array the notification object
		 */
		public function getSenderNotifications() {
			foreach( $this->getNotifications() as $cn )
				if( $cn->type == 'sender' )
					return $cn;
			return new CustomerNotification( array( 'customer_id' => $this->getId(), 'type' => 'sender' ) );
		}
		
		/**
		 * Returns the Notification object associated with this customer receiver settings
		 * 
		 * @return array the notification object
		 */
		public function getReceiverNotifications() {
			foreach( $this->getNotifications() as $cn )
				if( $cn->type == 'receiver' )
					return $cn;
			return new CustomerNotification( array( 'customer_id' => $this->getId(), 'type' => 'receiver' ) );
		}
		
		/**
		 * Returns the Invoice objects associated with this customer
		 * 
		 * Stores internally on first access to allow caching for
		 * faster consequtive lookups. Instantiates the Invoice objects
		 * with all data from DB.
		 * @return array the invoice objects
		 */
		public function getInvoices() {
			if( !isset( $this->invoices ) ) {
				$i = new Invoice();
				$this->invoices = $i->find( array( 'conditions' => array( 'customer_id' => $this->getId() ) ) );
			}
			return $this->invoices;
		}
		
		/**
		 * Returns the first administrator from the Contact objects associated with this customer
		 * @return Contact the found admin, null if noone is found
		 */
		public function getAdmin() {
			foreach( $this->getContacts() as $contact )
				if( $contact->admin )
					return $contact;
			return null;
		}
		
		/**
		 * Mark customer as confirmed and status "pending" after customer clicks the link in customer's email
		 * 
		 * @return boolean the result of saving operation
		 */
		public function confirm() {
			$this->update_attributes( array( 'confirmation_code' => null, 'status' => 'pending' ) );
			return $this->save();
		}
		
		/**
		 * Mark customer as "approved" and synchronize company with twinfield
		 * 
		 * @return boolean the result of saving operation
		 */
		public function approve() {
			$this->update_attributes( array( 'status' => 'approved' ) );
			$result = $this->save();
			$tw = new Twinfield();
			$tw->createCustomer( $this );
			return $result;
		}
		
		/**
		 * Mark customer as "suspended"
		 * 
		 * @return boolean the result of saving operation
		 */
		public function suspend() {
			$this->update_attributes( array( 'status' => 'suspended' ) );
			return $this->save();
		}
		
		/**
		 * Selects the administrator of the company/customer account
		 * 
		 * Mark one contact as administrator and all the rest as non-administrarors.
		 * @param int the contact ID of the administrator
		 */
		public function setAdministrator( $contact_id ) { 
			foreach( $this->getContacts() as $contact ) {
				$admin = false;
				if( $contact->getId() == $contact_id )
					$admin = true;
				$contact->update_attributes( array( 'admin' => $admin ) );
				$contact->save();
			}
		}
		
		/**
		 * Returns the current open balance of this customer
		 * 
		 * Calculates all invoices and payments and returns the balance with correct currency.
		 * @return Money the balance
		 */
		public function openBalance() {
			$balance = new Money( 0, 'EUR' );
			foreach( $this->getInvoices() as $invoice ) {
				$balance = $balance->subtract( $invoice->getTotalWithVat() );
				if( $payment = $invoice->getPayment() ) {
					$balance = $balance->add( new Money( $payment->amount, $payment->currency ) );
				}
			}
			return $balance;
		}
		
		/**
		 * The amount of money left to be invoiced without paying for this customer
		 * 
		 * Essentially, this is Max Credit - Open Balance.
		 * @return Money the credit left
		 */
		public function creditLeft() {
			$balance = $this->openBalance();
			$credit = new Money( $this->max_credit, $this->credit_currency ? $this->credit_currency : 'EUR' );
			if( $balance->getAmount() < 0 )
				$credit = $credit->add( $balance );
			return $credit;
		}
		
		/**
		 * Called when a customer notification must be sent
		 * 
		 * @param string the type of notification that is triggerred -
		 * e.g. shipment is picked up, en route, delivered, etc.
		 * @param Shipment the shipment that notification is
		 * connected with
		 */
		public function triggerNotifications( $type, $shipment ) {
			foreach( $this->getNotifications() as $notification )
				$notification->trigger( $type, $shipment );
		}
		
		/**
		 * Retrieve courier specific settings
		 * 
		 * @param string the courier
		 * @return CustomerProvider the settings object
		 */
		public function providerSettings( $courier_id ) {
			$cp = new CustomerProvider( array( 'customer_id' => $this->getId(), 'courier_id' => $courier_id ) );
			$settings = $cp->find( array( 'conditions' => array( 'customer_id' => $this->getId(), 'courier_id' => $courier_id ) ) );
			if( $settings )
				$cp = $settings[0];
			return $cp;
		}
		
		/**
		 * Log compare action
		 * 
		 * @return boolean the result
		 */
		public function logCompare() {
			$db = DB::instance();
			return $db->exec( "insert into compares( customer_id ) values( '".$this->getId()."' )" );
		}
		
		/**
		 * Helper method to retrieve shipment/profit statistics
		 * 
		 * @param string the type of statistics to retrieve
		 * @return int/float the statistics data
		 */
		public function stats( $type ) {
			$sql = '';
			$params = array();
			switch( $type ) {
				case 'total_shipments':
					$sql = "select count(*) from shipments where manifest_id is not null and customer_id = ?";
					break;
				case 'avg_shipments':
					$sql = "select avg( cnt ) from
						(select count( s.id ) as cnt, w.yw from
							weeks w
							left join shipments s on w.yw = yearweek( s.created_at ) and s.manifest_id is not null and s.customer_id = ?
							where w.yw >= (select yearweek( c.created_at ) from customers c where c.id = ? ) and w.yw <= yearweek( curdate() )
							group by w.yw
						) tmp";
					$params[] = $this->getId();
					break;
				case 'last_shipments':
					$sql = "select count(*) from shipments where manifest_id is not null and created_at between ? and ? and customer_id = ?";
					$end = strtotime( 'last Sunday' );
					$start = strtotime( 'last Monday', $end );
					$params[] = date( 'Y-m-d 00:00:00', $start );
					$params[] = date( 'Y-m-d 23:59:59', $end );
					break;
				case 'total_profit':
					$sql = "select sum(total_price)-sum(provider_price) from shipments where manifest_id is not null and customer_id = ?";
					break;
				case 'avg_profit':
					$sql = "select avg( prft ) from
						(select ifnull(sum( s.total_price ) - sum( s.provider_price ),0) as prft, w.yw from
							weeks w
							left join shipments s on w.yw = yearweek( s.created_at ) and s.manifest_id is not null and s.customer_id = ?
							where w.yw >= (select yearweek( c.created_at ) from customers c where c.id = ? ) and w.yw <= yearweek( curdate() )
							group by w.yw
						) tmp";
					$params[] = $this->getId();
					break;
				case 'last_profit':
					$sql = "select sum(total_price)-sum(provider_price) from shipments where manifest_id is not null and created_at between ? and ? and customer_id = ?";
					$end = strtotime( 'last Sunday' );
					$start = strtotime( 'last Monday', $end );
					$params[] = date( 'Y-m-d 00:00:00', $start );
					$params[] = date( 'Y-m-d 23:59:59', $end );
					break;
				case 'compares':
					$sql = "select count(*) from compares where YEAR(created_at) = ? and MONTH(created_at) = ? and customer_id = ?";
					$params[] = date( 'Y', strtotime( 'last month' ) );
					$params[] = date( 'n', strtotime( 'last month' ) );
					break;
				case 'orders':
					$sql = "select count(*) from shipments where manifest_id is not null and YEAR(created_at) = ? and MONTH(created_at) = ? and customer_id = ?";
					$params[] = date( 'Y', strtotime( 'last month' ) );
					$params[] = date( 'n', strtotime( 'last month' ) );
					break;
				case 'compare_order_rate':
					$sql = "select (select count(*) from shipments where manifest_id is not null and YEAR(created_at) = ? and MONTH(created_at) = ? and customer_id = ?) / (select count(*) from compares where YEAR(created_at) = ? and MONTH(created_at) = ? and customer_id = ?)";
					$params[] = date( 'Y', strtotime( 'last month' ) );
					$params[] = date( 'n', strtotime( 'last month' ) );
					$params[] = $this->getId();
					$params[] = date( 'Y', strtotime( 'last month' ) );
					$params[] = date( 'n', strtotime( 'last month' ) );
					break;
			}
			$params[] = $this->getId();
			$db = DB::instance();
			$stmt = $db->prepare( $sql );
			$stmt->execute( $params );
			$row = $stmt->fetch();
			$data = $row[0];
			if( $type == 'compare_order_rate' )
				return round( $data * 100 )."%";
			if( !$data || floatval( $data ) == 0 )
				return 0;
			if( ceil( $data ) != $data )
				return number_format( $data, 2 );
			else
				return $data;
		}
		
		/**
		 * Associates manual courier contacts with this customer
		 * 
		 * @param array the array of manual courier contact ids
		 * @return boolean the result of the operation
		 */
		public function setAccountManagers( $contact_ids ) {
			$db = DB::instance();
			$db->exec( "delete from customer_account_managers where customer_id = ".$this->getId() );
			$stmt = $db->prepare( "insert into customer_account_managers( customer_id, courier_contact_id ) values( ?, ? )" );
			if(!empty($contact_ids)){
                if(is_array($contact_ids)){
                    foreach( $contact_ids as $contact_id )
			if( $contact_id > 0 )
			        $stmt->execute( array( $this->getId(), $contact_id ) );
                }else{
			if( $contact_ids > 0 )
				$stmt->execute( array( $this->getId(), $contact_ids ) );
                }
            }
			return true;
		}
        
        public function getAccountManagers() {
            $db = DB::instance();
            $stmt = $db->prepare( "select courier_contact_id from customer_account_managers where customer_id = ?" );
            $stmt->execute( array( $this->getId() ) );
            $rows = $stmt->fetchAll();
            return $rows;
        }
        
        public function searchTerm( $searchterm ){
            if(!empty($searchterm)){
                $db = DB::instance();
/*                $stmt = $db->prepare( "SELECT 
                                         customers.*,
                                         MATCH(customers.phone) AGAINST(?) as cscore, 
                                         MATCH(addresses.company) AGAINST(?) as ascore_comp, 
                                         MATCH(addresses.city) AGAINST(?) as ascore_city, 
                                         MATCH(addresses.zip) AGAINST(?) as ascore_zip, 
                                         MATCH(addresses.country) AGAINST(?) as ascore_country, 
                                         MATCH(addresses.company, addresses.zip, addresses.country) AGAINST(?) as ascore_all, 
                                         MATCH(addresses.email) AGAINST(?) as ascore_email 
                                       FROM 
                                         customers
                                       LEFT JOIN addresses ON customers.address_id = addresses.id 
                                       WHERE
                                         MATCH(addresses.company) AGAINST(?) OR
                                         MATCH(addresses.email) AGAINST(?) OR 
                                         MATCH(addresses.city) AGAINST(?) OR 
                                         MATCH(addresses.zip) AGAINST(?) OR 
                                         MATCH(addresses.country) AGAINST(?) OR
                                         MATCH(addresses.company, addresses.zip, addresses.country) AGAINST(?) OR
                                         MATCH(customers.phone) AGAINST(?)
                                       " );
                
                $stmt->execute( array( $searchterm, $searchterm, $searchterm, $searchterm, $searchterm, $searchterm, $searchterm, 
                                        $searchterm, $searchterm, $searchterm, $searchterm, $searchterm, $searchterm, $searchterm  ) );*/
		$stmt = $db->prepare( "select customers.* from customers left join addresses on customers.address_id = addresses.id where
			addresses.company like ? or
			addresses.email like ? or
			addresses.city like ? or
			addresses.zip like ? or
			addresses.country like ? or
			customers.phone like ?" );
		$values = array();
		for( $i = 0; $i < 6; $i++ )
			$values[] = "%$searchterm%";
		$stmt->execute( $values );
                $rows = $stmt->fetchAll();
                return $rows;        
            }else{
                return false;
            }
        }
	}
?>
