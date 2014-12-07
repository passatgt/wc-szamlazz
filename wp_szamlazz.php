<?php 
/*
Plugin Name: WooCommerce Szamlazz.hu
Plugin URI: http://visztpeter.me
Description: Számlázz.hu összeköttetés WooCommercehez
Author: Viszt Péter
Version: 1.0.3
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//Generate stuff on plugin activation
function wc_szamlazz_activate() {
	$upload_dir =  wp_upload_dir();

	$files = array(
		array(
			'base' 		=> $upload_dir['basedir'] . '/wc_szamlazz',
			'file' 		=> 'index.html',
			'content' 	=> ''
		)
	);

	foreach ( $files as $file ) {
		if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
			if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
				fwrite( $file_handle, $file['content'] );
				fclose( $file_handle );
			}
		}
	}   
}
register_activation_hook( __FILE__, 'wc_szamlazz_activate' );

class WC_Szamlazz {

	public static $plugin_prefix;
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basename;
	public static $version;

    //Construct
    public function __construct() {

		//Default variables
		self::$plugin_prefix = 'wc_szamlazz_';
		self::$plugin_basename = plugin_basename(__FILE__);
		self::$plugin_url = plugin_dir_url(self::$plugin_basename);
		self::$plugin_path = trailingslashit(dirname(__FILE__));
		self::$version = '1.0.3'; 


		add_action( 'admin_init', array( $this, 'wc_szamlazz_admin_init' ) );

    	add_filter( 'woocommerce_general_settings', array( $this, 'szamlazz_settings' ), 20, 1 );
		add_action( 'add_meta_boxes', array( $this, 'wc_szamlazz_add_metabox' ) );

        add_action( 'wp_ajax_wc_szamlazz_generate_invoice', array( $this, 'generate_invoice_with_ajax' ) ); 
        add_action( 'wp_ajax_nopriv_wc_szamlazz_generate_invoice', array( $this, 'generate_invoice_with_ajax' ) );

        add_action( 'wp_ajax_wc_szamlazz_already', array( $this, 'wc_szamlazz_already' ) ); 
        add_action( 'wp_ajax_nopriv_wc_szamlazz_already', array( $this, 'wc_szamlazz_already' ) );

        add_action( 'wp_ajax_wc_szamlazz_already_back', array( $this, 'wc_szamlazz_already_back' ) ); 
        add_action( 'wp_ajax_nopriv_wc_szamlazz_already_back', array( $this, 'wc_szamlazz_already_back' ) );

		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_complete' ) );

		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_listing_actions' ) );
		
    }
    
    //Add CSS & JS
	public function wc_szamlazz_admin_init() {
        wp_enqueue_script( 'szamlazz_js', plugins_url( '/global.js',__FILE__ ), array('jquery'), TRUE );
        wp_enqueue_style( 'szamlazz_css', plugins_url( '/global.css',__FILE__ ) );

		$wc_szamlazz_local = array( 'loading' => plugins_url( '/images/ajax-loader.gif',__FILE__ ) );
		wp_localize_script( 'szamlazz_js', 'wc_szamlazz_params', $wc_szamlazz_local );

    }

	//Settings
	public function szamlazz_settings( $settings ) {

		$settings[] = array(
			'type' => 'title',
			'title' => __( 'Szamlazz.hu Beállítások', 'wc-szamlazz' ),
			'id' => 'woocommerce_szamlazz_options',
		);

		$settings[] = array(
			'title'    => __( 'Felhasználónév', 'wc-szamlazz' ),
			'id'       => 'wc_szamlazz_username',
			'type'     => 'text'
		);

		$settings[] = array(
			'title'    => __( 'Jelszó', 'wc-szamlazz' ),
			'id'       => 'wc_szamlazz_password',
			'type'     => 'password'
		);

		$settings[] = array(
			'title'    => __( 'Számla típusa', 'wc-szamlazz' ),
			'id'       => 'wc_szamlazz_invoice_type',
			'class'    => 'chosen_select',
			'css'      => 'min-width:300px;',
			'type'     => 'select',
			'options'     => array(
				'electronic'  => __( 'Elektronikus', 'wc-szamlazz' ),
				'paper' => __( 'Papír', 'wc-szamlazz' )
			)
		);

		$settings[] = array(
			'title'    => __( 'Fizetési határidő(nap)', 'wc-szamlazz' ),
			'id'       => 'wc_szamlazz_payment_deadline',
			'type'     => 'text'
		);

		$settings[] = array(
			'title'    => __( 'Megjegyzés', 'wc-szamlazz' ),
			'id'       => 'wc_szamlazz_note',
			'type'     => 'text'
		);	

		$settings[] = array(
			'title'    => __( 'Automata számlakészítés', 'wc-szamlazz' ),
			'id'       => 'wc_szamlazz_auto',
			'type'     => 'checkbox',
			'desc'     => __( 'Ha be van kapcsolva, akkor a rendelés lezárásakor automatán kiállításra kerül a számla és a szamlazz.hu elküldi a vásárló emailcímére.', 'wc-szamlazz' ),
		);

		$settings[] =  array( 'type' => 'sectionend', 'id' => 'woocommerce_szamlazz_options');

		return $settings;

	}
	
	//Meta box on order page
	public function wc_szamlazz_add_metabox( $post_type ) {
	
		add_meta_box('custom_order_option', 'Számlázz.hu számla', array( $this, 'render_meta_box_content' ), 'shop_order', 'side');

	}

	//Render metabox content
	public function render_meta_box_content($post) {
		?>
			
		<?php if(!get_option('wc_szamlazz_username') || !get_option('wc_szamlazz_password')): ?>
			<p style="text-align: center;"><?php _e('A számlakészítéshez meg kell adnod a számlázz.hu felhasználóneved és jelszavad a Woocommerce beállításokban!','wc-szamlazz'); ?></p>
		<?php else: ?>		
			<div id="wc-szamlazz-messages"></div>			
			<?php if(get_post_meta($post->ID,'_wc_szamlazz_own',true)): ?>
				<div style="text-align:center;" id="szamlazz_already_div">
					<?php $note = get_post_meta($post->ID,'_wc_szamlazz_own',true); ?>
					<p><?php _e('A számlakészítés ki lett kapcsolva, mert: ','wc-szamlazz'); ?><strong><?php echo $note; ?></strong><br>
					<a id="wc_szamlazz_already_back" href="#" data-nonce="<?php echo wp_create_nonce( "wc_already_invoice" ); ?>" data-order="<?php echo $post->ID; ?>"><?php _e('Visszakapcsolás','wc-szamlazz'); ?></a>
					</p>
				</div>	
			<?php endif; ?>	
			<?php if($this->is_invoice_generated($post->ID) && !get_post_meta($post->ID,'_wc_szamlazz_own',true)): ?>
				<div style="text-align:center;">
					<p><?php echo __('Számla sikeresen létrehozva és elküldve a vásárlónak emailben.','wc-szamlazz'); ?></p>
					<p><?php _e('A számla sorszáma:','wc-szamlazz'); ?> <strong><?php echo get_post_meta($post->ID,'_wc_szamlazz',true); ?></strong></p>
					<p><a href="<?php echo $this->generate_download_link($post->ID); ?>" id="wc_szamlazz_download" data-nonce="<?php echo wp_create_nonce( "wc_generate_invoice" ); ?>" class="button button-primary" target="_blank"><?php _e('Számla megtekintése','wc-szamlazz'); ?></a></p>
				</div>
			<?php else: ?>
				<div style="text-align:center;<?php if(get_post_meta($post->ID,'_wc_szamlazz_own',true)): ?>display:none;<?php endif; ?>" id="wc-szamlazz-generate-button">
					<p><a href="#" id="wc_szamlazz_generate" data-order="<?php echo $post->ID; ?>" data-nonce="<?php echo wp_create_nonce( "wc_generate_invoice" ); ?>" class="button button-primary" target="_blank"><?php _e('Számlakészítés','wc-szamlazz'); ?></a><br><a href="#" id="wc_szamlazz_options"><?php _e('Opciók','wc-szamlazz'); ?></a></p>
					<div id="wc_szamlazz_options_form" style="display:none;">
						<div class="fields">
						<h4><?php _e('Megjegyzés','wc-szamlazz'); ?></h4>
						<input type="text" id="wc_szamlazz_invoice_note" value="<?php echo get_option('wc_szamlazz_note'); ?>" />
						<h4><?php _e('Fizetési határidő(nap)','wc-szamlazz'); ?></h4>
						<input type="text" id="wc_szamlazz_invoice_deadline" value="<?php echo get_option('wc_szamlazz_payment_deadline'); ?>" />
						</div>
						<a id="wc_szamlazz_already" href="#" data-nonce="<?php echo wp_create_nonce( "wc_already_invoice" ); ?>" data-order="<?php echo $post->ID; ?>"><?php _e('Számlakészítés kikapcsolása','wc-szamlazz'); ?></a>
					</div>
					<?php if(get_option('wc_szamlazz_auto') == 'yes'): ?>
					<p><small><?php _e('A számla automatikusan elkészül és el lesz küldve a vásárlónak, ha a rendelés állapota befejezettre lesz átállítva.','wc-szamlazz'); ?></small></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		
		<?php
	}
	
	//Generate Invoice with Ajax
	public function generate_invoice_with_ajax() {
        check_ajax_referer( 'wc_generate_invoice', 'nonce' );
        if( true ) {
        	$orderid = $_POST['order'];
			$return_info = $this->generate_invoice($orderid);
			wp_send_json_success($return_info);	
        }
            	
	}

	//Generate XML for Szamla Agent
	public function generate_invoice($orderId) {
		global $wpdb, $woocommerce;
		$order = new WC_Order($orderId);
		$order_items = $order->get_items();

		//Build Xml
		$szamla = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xmlszamla xmlns="http://www.szamlazz.hu/xmlszamla" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.szamlazz.hu/xmlszamla xmlszamla.xsd"></xmlszamla>');
		
		//If custom details
		if(isset($_POST['note']) && isset($_POST['deadline'])) {
			$note = $_POST['note'];
			$deadline = $_POST['deadline'];
		} else {
			$note = get_option('wc_szamlazz_note');
			$deadline = get_option('wc_szamlazz_payment_deadline');			
		}
		
		//Account & Invoice settings
		$beallitasok = $szamla->addChild('beallitasok');
		$beallitasok->addChild('felhasznalo', get_option('wc_szamlazz_username'));
		$beallitasok->addChild('jelszo', get_option('wc_szamlazz_password'));
		if(get_option('wc_szamlazz_invoice_type') != 'paper') {
			$beallitasok->addChild('eszamla', 'true');			
		}
		$beallitasok->addChild('szamlaLetoltes', 'true');
		
		//Invoice details
		$fejlec = $szamla->addChild('fejlec');
		$fejlec->addChild('keltDatum', date('Y-m-d') );
		$fejlec->addChild('teljesitesDatum', date('Y-m-d') );
		if($deadline) {
			$fejlec->addChild('fizetesiHataridoDatum', date('Y-m-d', strtotime('+'.$deadline.' days')));
		} else {
			$fejlec->addChild('fizetesiHataridoDatum', date('Y-m-d'));
		}
		$fejlec->addChild('fizmod',$order->payment_method_title);
		$fejlec->addChild('penznem',$order->get_order_currency());
		$fejlec->addChild('szamlaNyelve', 'hu');
		$fejlec->addChild('megjegyzes', $note);
		$fejlec->addChild('rendelesSzam', $orderId);
		$fejlec->addChild('elolegszamla', 'false');
		$fejlec->addChild('vegszamla', 'false');
		
		//Seller details
		$elado = $szamla->addChild('elado');
		
		//Customer details
		$vevo = $szamla->addChild('vevo');
		$vevo->addChild('nev', ($order->billing_company ? $order->billing_company.' - ' : '').$order->billing_first_name.' '.$order->billing_last_name );
		$vevo->addChild('irsz',$order->billing_postcode);
		$vevo->addChild('telepules',$order->billing_city);
		$vevo->addChild('cim',$order->billing_address_1);
		$vevo->addChild('email',$order->billing_email);
		$vevo->addChild('adoszam', '');
		$vevo->addChild('telefonszam',$order->billing_phone);
		
		//Customer Shipping details if needed
		if ( $order->shipping_address ) {
			$vevo->addChild('postazasiNev', ($order->shipping_company ? $order->shipping_company.' - ' : '').$order->shipping_first_name.' '.$order->shipping_last_name );
			$vevo->addChild('postazasiIrsz',$order->shipping_postcode);
			$vevo->addChild('postazasiTelepules',$order->shipping_city);
			$vevo->addChild('postazasiCim',$order->shipping_address_1);
		}

		//Order Items
		$tetelek = $szamla->addChild('tetelek');
		foreach( $order_items as $termek ) {  
			$tetel = $tetelek->addChild('tetel');
			$tetel->addChild('megnevezes',$termek["name"]);
			$tetel->addChild('mennyiseg',$termek["qty"]);
			$tetel->addChild('mennyisegiEgyseg','');
			$tetel->addChild('nettoEgysegar',round($termek["line_subtotal"])/$termek["qty"]);
			$tetel->addChild('afakulcs',round((round($termek["line_subtotal_tax"])/round($termek["line_subtotal"]))*100));
			$tetel->addChild('nettoErtek',round($termek["line_total"]));
			$tetel->addChild('afaErtek',round($termek["line_subtotal_tax"]));
			$tetel->addChild('bruttoErtek',round($termek["line_total"])+round($termek["line_tax"]));
			$tetel->addChild('megjegyzes','');
		}

		//Shipping
		if($order->get_shipping_methods()) {
			$tetel = $tetelek->addChild('tetel');
			$tetel->addChild('megnevezes','Szállítás');
			$tetel->addChild('mennyiseg','1');
			$tetel->addChild('mennyisegiEgyseg','');
			$tetel->addChild('nettoEgysegar',round($order->order_shipping));
			if($order->order_shipping == 0) {
				$tetel->addChild('afakulcs','0');	
			} else {
				$tetel->addChild('afakulcs',round((round($order->order_shipping_tax)/round($order->order_shipping))*100));				
			}
			$tetel->addChild('nettoErtek',round($order->order_shipping));
			$tetel->addChild('afaErtek',round($order->order_shipping_tax));
			$tetel->addChild('bruttoErtek',round($order->order_shipping)+round($order->order_shipping_tax));
			$tetel->addChild('megjegyzes','');
		}
		
		//Discount
		if ( $order->order_discount > 0 ) {
			$tetel = $tetelek->addChild('tetel');
			$tetel->addChild('megnevezes','Kedvezmény');
			$tetel->addChild('mennyiseg','1');
			$tetel->addChild('mennyisegiEgyseg','');
			$tetel->addChild('nettoEgysegar',-$order->order_discount);
			$tetel->addChild('afakulcs',0);
			$tetel->addChild('nettoErtek',-$order->order_discount);
			$tetel->addChild('afaErtek',0);
			$tetel->addChild('bruttoErtek',-$order->order_discount);
			$tetel->addChild('megjegyzes','');
		}

		//Generate XML
		$xml = $szamla->asXML();
		
		//Temporarily save XML
		$UploadDir = wp_upload_dir();
		$UploadURL = $UploadDir['basedir'];
		$location  = realpath($UploadURL . "/wc_szamlazz/");
		$xmlfile = $location.'/'.$orderId.'.xml';
		$test = file_put_contents($xmlfile, $xml);
		
		//Generate cookie
		$cookie_file = $location.'/szamlazz_cookie.txt';

		//Agent URL
		$agent_url = 'https://www.szamlazz.hu/szamla/';
			
		//Geerate Cookie if not already exists
		if (!file_exists($cookie_file)) { 
			file_put_contents($cookie_file, '');
		}
			
		// a CURL inicializálása
		$ch = curl_init($agent_url);
			
		// A curl hívás esetén tanúsítványhibát kaphatunk az SSL tanúsítvány valódiságától 
		// függetlenül, ez az alábbi CURL paraméter állítással kiküszöbölhető, 
		// ilyenkor nincs külön SSL ellenőrzés:
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
		// POST-ban küldjük az adatokat
		curl_setopt($ch, CURLOPT_POST, true);
			
		// Kérjük a HTTP headert a válaszba, fontos információk vannak benne
		curl_setopt($ch, CURLOPT_HEADER, true);
			
		// változóban tároljuk a válasz tartalmát, nem írjuk a kimenetbe
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
		// Beállítjuk, hol van az XML, amiből számlát szeretnénk csinálni (= file upload)
		// az xmlfile-t itt fullpath-al kell megadni
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('action-xmlagentxmlfile'=>'@' . $xmlfile)); 
			
		// 30 másodpercig tartjuk fenn a kapcsolatot (ha valami bökkenő volna)
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			
		// Itt állítjuk be, hogy az érkező cookie a $cookie_file-ba kerüljön mentésre
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); 
			
		// Ha van már cookie file-unk, és van is benne valami, elküldjük a Számlázz.hu-nak
		if (file_exists($cookie_file) && filesize($cookie_file) > 0) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); 
		}
			
		// elküldjük a kérést a Számlázz.hu felé, és eltároljuk a választ
		$agent_response = curl_exec($ch);
			
		// kiolvassuk a curl-ból volt-e hiba
		$http_error = curl_error($ch);
			
		// ezekben a változókban tároljuk a szétbontott választ
		$agent_header = '';
		$agent_body = '';
		$agent_http_code = '';
			
		// lekérjük a válasz HTTP_CODE-ját, ami ha 200, akkor a http kommunikáció rendben volt
		// ettől még egyáltalán nem biztos, hogy a számla elkészült
		$agent_http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			
		// a válasz egy byte kupac, ebből az első "header_size" darab byte lesz a header
		$header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
			
		// a header tárolása, ebben lesznek majd a számlaszám, bruttó nettó összegek, errorcode, stb.
		$agent_header = substr($agent_response, 0, $header_size);
			
		// a body tárolása, ez lesz a pdf, vagy szöveges üzenet
		$agent_body = substr( $agent_response, $header_size );
			
		// a curl már nem kell, lezárjuk
		curl_close($ch);
			
		// a header soronként tartalmazza az információkat, egy tömbbe teszük a külön sorokat
		$header_array = explode("\n", $agent_header);
			
		// ezt majd true-ra állítjuk ha volt hiba
		$volt_hiba = false;
			
		// ebben lesznek a hiba információk, plusz a bodyban
		$agent_error = '';
		$agent_error_code = '';
			
		// menjünk végig a header sorokon, ami "szlahu"-val kezdődik az érdekes nekünk és írjuk ki
		foreach ($header_array as $val) {
			if (substr($val, 0, strlen('szlahu')) === 'szlahu') {
				// megvizsgáljuk, hogy volt-e hiba
				if (substr($val, 0, strlen('szlahu_error:')) === 'szlahu_error:') {
					// sajnos volt
					$volt_hiba = true;
					$agent_error = substr($val, strlen('szlahu_error:'));
				}
				if (substr($val, 0, strlen('szlahu_error_code:')) === 'szlahu_error_code:') {
					// sajnos volt
					$volt_hiba = true;
					$agent_error_code = substr($val, strlen('szlahu_error_code:'));
				}
			} 
		}
			
		// ha volt http hiba dobunk egy kivételt
		$response = array();
		$response['error'] = false;
		if ( $http_error != "" ) {
			$response['error'] = true;
			$response['messages'][] = 'Http hiba történt:'.$http_error;
			return $response;
		}
		
		//Delete the XML
		unlink($xmlfile);
			
		if ($volt_hiba) {
			$response['error'] = true;
			
			// ha a számla nem készült el kiírjuk amit lehet
			$response['messages'][] = 'Agent hibakód: '.$agent_error_code;
			$response['messages'][] = 'Agent hibaüzenet: '.urldecode($agent_error);
			$response['messages'][] = 'Agent válasz: '.urldecode($agent_body);

			//Update order notes
			$order->add_order_note( __( 'Szamlazz.hu számlakészítás sikertelen! Agent hibakód: ', 'wc-szamlazz' ).$agent_error_code );
			
			// dobunk egy kivételt
			return $response;
			
		} else {
			
			//Get the Invoice ID from the response header
			$szlahu_szamlaszam = '';
			foreach ($header_array as $val) {
				if (substr($val, 0, strlen('szlahu_szamlaszam')) === 'szlahu_szamlaszam') {
					$szlahu_szamlaszam = substr($val, strlen('szlahu_szamlaszam:'));
					break;
				} 
			}
			
			//Build response array
			$response['messages'][] = __('Számla sikeresen létrehozva és elküldve a vásárlónak emailben.','wc-szamlazz');
			$response['invoice_name'] = $szlahu_szamlaszam;
			
			//Store as a custom field
			update_post_meta( $orderId, '_wc_szamlazz', $szlahu_szamlaszam );
			
			//Update order notes
			$order->add_order_note( __( 'Szamlazz.hu számla sikeresen létrehozva. A számla sorszáma: ', 'wc-szamlazz' ).$szlahu_szamlaszam );
			
			//Download & Store PDF - generate a random file name so it will be downloadable later only by you
			$random_file_name = substr(md5(rand()),5);
			$pdf_file_name = 'szamla_'.$random_file_name.'_'.$orderId.'.pdf';
			$pdf_file = $location.'/'.$pdf_file_name;
			file_put_contents($pdf_file, $agent_body); 
			
			//Store the filename
			update_post_meta( $orderId, '_wc_szamlazz_pdf', $pdf_file_name );
			
			//Return the download url
			$response['link'] = '<p><a href="'.$this->generate_download_link($orderId).'" id="wc_szamlazz_download" class="button button-primary" target="_blank">'.__('Számla megtekintése','wc-szamlazz').'</a></p>';

			return $response;
		}
		          	
	}	
	
	//Autogenerate invoice
	public function on_order_complete( $order_id ) {
	
		//Only generate invoice, if it wasn't already generated & only if automatic invoice is enabled
		if(get_option('wc_szamlazz_auto') == 'yes') {
			if(!$this->is_invoice_generated($order_id)) {				
				$return_info = $this->generate_invoice($order_id);	
			}			
		}
		
	}
	
	//Check if it was already generated or not
	public function is_invoice_generated( $order_id ) {
		$invoice_name = get_post_meta($order_id,'_wc_szamlazz',true);
		$invoice_own = get_post_meta($order_id,'_wc_szamlazz_own',true);
		if($invoice_name || $invoice_own) {
			return true;
		} else {
			return false;
		}
	}
	
	//Add icon to order list to show invoice
	public function add_listing_actions( $order ) {
		if($this->is_invoice_generated($order->id)):
		?>
			<a href="<?php echo $this->generate_download_link($order->id); ?>" class="button tips wc_szamlazz" target="_blank" alt="" data-tip="<?php _e('Szamlazz.hu számla','wc-szamlazz'); ?>">
				<img src="<?php echo WC_Szamlazz::$plugin_url . 'images/invoice.png'; ?>" alt="" width="16" height="16">
			</a>
		<?php
		endif;
	}
	
	//Generate download url
	public function generate_download_link( $order_id ) {
		if($order_id) {
			$pdf_name = get_post_meta($order_id,'_wc_szamlazz_pdf',true);
			$UploadDir = wp_upload_dir();
			$UploadURL = $UploadDir['baseurl'];
			$pdf_file_url = $UploadURL.'/wc_szamlazz/'.$pdf_name;
			return $pdf_file_url;
		} else {
			return false;
		}
	}	
	
	//Get available checkout methods and ayment gateways
	public function get_available_payment_gateways() {
		$available_gateways = WC()->payment_gateways->payment_gateways();
		$available = array();
		$available['none'] = __('Válassz fizetési módot','wc-szamlazz');
		foreach ($available_gateways as $available_gateway) {
			$available[$available_gateway->id] = $available_gateway->title;
		}
		return $available;
	}	
	
	//If the invoice is already generated without the plugin
	public function wc_szamlazz_already() {
        check_ajax_referer( 'wc_already_invoice', 'nonce' );
        if( true ) {
			if ( !current_user_can( 'edit_shop_order' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}			
        	$orderid = $_POST['order'];
			$note = $_POST['note'];
			update_post_meta( $orderid, '_wc_szamlazz_own', $note );
			
			$response = array();
			$response['error'] = false;
			$response['messages'][] = __('Saját számla sikeresen hozzáadva.','wc-szamlazz');
			$response['invoice_name'] = $note;
			
			wp_send_json_success($response);
        }
            	
	}		
	
	//If the invoice is already generated without the plugin, turn it off
	public function wc_szamlazz_already_back() {
        check_ajax_referer( 'wc_already_invoice', 'nonce' );
        if( true ) {
			if ( !current_user_can( 'edit_shop_order' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}			
        	$orderid = $_POST['order'];
			$note = $_POST['note'];
			update_post_meta( $orderid, '_wc_szamlazz_own', '' );
			
			$response = array();
			$response['error'] = false;
			$response['messages'][] = __('Visszakapcsolás sikeres.','wc-szamlazz');
			
			wp_send_json_success($response);
        }
            	
	}	
}

$GLOBALS['wc_szamlazz'] = new WC_Szamlazz();

?>