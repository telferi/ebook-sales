<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ✅ Helyettesítés csak generáláskor, nem a mentésnél!
function replace_placeholders($prompt, $writing_style, $writing_tone, $language) {
    return str_replace(
        array('<Írási stílus>', '<Írási hangnem>', '<Nyelv>'),
        array($writing_style, $writing_tone, $output_language),
        $prompt
    );
}

class AI_Setup {
    public function __construct() {
        add_action('admin_menu', array($this, 'register_ai_settings_page'));
    }

    public function register_ai_settings_page() {
        add_submenu_page(
            'ebook-sales',      
            'AI Setup',         
            'AI Setup',         
            'manage_options',   
            'ai-setup',         
            array($this, 'display_settings_page')
        );
    }

    public function display_settings_page() {
        if (current_user_can('manage_options') && isset($_POST['openai_api_key']) && check_admin_referer('save_openai_api_key')) {
            $api_key = sanitize_text_field($_POST['openai_api_key']);
            update_option('openai_api_key', $api_key);

            if (isset($_POST['openai_api_model'])) {
                $model = sanitize_text_field($_POST['openai_api_model']);
                update_option('openai_api_model', $model);
            }

            if (isset($_POST['system_prompt'])) {
                // 🔹 Helyesen mentjük a system promptot anélkül, hogy módosítanánk a placeholder-eket
                $prompt = htmlspecialchars_decode($_POST['system_prompt'], ENT_QUOTES);
                update_option('basic_system_prompt', $prompt);
                update_option('system_prompt', $prompt);
                
                echo '<div class="updated"><p>Beállítások elmentve.</p></div>';
            }
        }

        $current_key   = get_option('openai_api_key', '');
        $current_model = get_option('openai_api_model', '');
        $default_system_prompt = "TE EGY PRÉMIUM EBOOK MARKETING SZAKÉRTŐ VAGY, AKINEK FELADATA LENYŰGÖZŐ, ÉRDEKES ÉS MEGGYŐZŐ ISMERTETŐT ÍRNI A FELTÖLTÖTT EBOOKHOZ. A CÉL, HOGY AZ ISMERTETŐ FELKELTSE AZ OLVASÓ FIGYELMÉT ÉS ÖSZTÖNÖZZE A VÁSÁRLÁST.  
 
 ### FELADAT:  
 - **ANALIZÁLD** az eBook tartalmát és azonosítsd a legfontosabb témákat.  
 - **FOGALMAZD MEG** röviden és érthetően, miről szól az eBook.  
 - **HANGSÚLYOZD** az olvasó számára nyújtott előnyöket és értéket.  
 - **ALKALMAZKODJ** a Következő preferenciákhoz: 
 	1, A szöveg stílusa legyen <Írási stílus> stílusú.
 	2, A szöveg hangneme legyen <Írási hangnem> hangnemű.
 	3, A szöveg nyelve legyen <Nyelv> nyelvű.
 
 ### FORMÁTUM:  
 A generált szöveg legyen:  
 - Rövid, tömör (maximum 3-5 mondat).  
 - Meggyőző és figyelemfelkeltő.  
 - Világosan kiemelve az eBook fő témáját és hasznát.  
 
 ### PÉLDA KIMENETEK:  
 
 **[Önfejlesztő eBook esetén]**  
 🔹 \"Szeretnéd kihozni magadból a legtöbbet? Ez az eBook lépésről lépésre megmutatja, hogyan építs sikeres szokásokat, növeld a produktivitásod és érd el a céljaid. Kezdd el még ma!\"  
 
 **[Üzleti eBook esetén]**  
 💼 \"Ismerd meg a modern üzleti stratégiák titkait! Ez az útmutató segít növelni bevételeidet, hatékonyabbá tenni vállalkozásodat és megalapozni a hosszú távú sikert.\"  
 
 **[Regény esetén]**  
 📖 \"Egy lebilincselő történet tele izgalommal és fordulatokkal! Merülj el egy világban, ahol minden döntés számít, és fedezd fel a karakterek lenyűgöző történetét.\"  
 
 ### MIT NE TEGYÉL:  
 ❌ NE generálj túl hosszú vagy unalmas ismertetőt.  
 ❌ NE írj túl általánosan – emeld ki a konkrét értékajánlatot.  
 ❌ NE hagyd figyelmen kívül az Írási stílus, Írási hangnem és Nyelv beállításokat.  
 
 🔹 A generált szöveg mindig legyen *érdekes, figyelemfelkeltő és ösztönző*!" 
 ;
        $system_prompt = get_option('system_prompt', $default_system_prompt);
        ?>
        <div class="wrap">
            <h1>OpenAI API Beállítások</h1>
            <form method="post" action="">
                <?php wp_nonce_field('save_openai_api_key'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">API Kulcs</th>
                        <td><input type="password" name="openai_api_key" value="<?php echo esc_attr($current_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Model Tipusa</th>
                        <td>
                            <select name="openai_api_model">
                                <?php
                                $models = array();
                                if (!empty($current_key)) {
                                    $response = wp_remote_get('https://api.openai.com/v1/models', array(
                                        'headers' => array(
                                            'Authorization' => 'Bearer ' . trim($current_key),
                                        ),
                                    ));

                                    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                                        $body = json_decode(wp_remote_retrieve_body($response), true);
                                        if (!empty($body['data'])) {
                                            foreach ($body['data'] as $model) {
                                                $models[] = esc_html($model['id']);
                                            }
                                        }
                                    }
                                }

                                foreach($models as $model): ?>
                                    <option value="<?php echo esc_attr($model); ?>" <?php selected($current_model, $model); ?>><?php echo esc_html($model); ?></option>
                                <?php endforeach; ?>
                                <?php if(empty($models)): ?>
                                    <option value="">Nincs elérhető modell</option>
                                <?php endif; ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">System Prompt</th>
                        <td>
                            <textarea name="system_prompt" class="large-text code" rows="10"><?php echo esc_textarea($system_prompt); ?></textarea>
                            <p class="description">
                                Az alap promptban **benne kell maradniuk** a következő elemeknek:  
                                <code>&lt;Írási stílus&gt;</code>, <code>&lt;Írási hangnem&gt;</code>, <code>&lt;Nyelv&gt;</code>.  
                                Ezeket **a generálás gomb fogja behelyettesíteni** az eBook posztnál.
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Mentés'); ?>
            </form>
        </div>
        <?php
    }
}

// ✅ Inicializáljuk az AI setup-ot
new AI_Setup();
?>
