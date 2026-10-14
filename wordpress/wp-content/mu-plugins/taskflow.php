<?php
/**
 * Plugin Name: Taskflow — zadania i projekty
 * Description: Własny panel TODO z projektami, priorytetami i tablicą Kanban.
 * Version: 1.0.0
 */
defined('ABSPATH') || exit;
if (defined('TASKFLOW_DEMO') && TASKFLOW_DEMO && !defined('WP_CLI')) {
 // The sandbox embeds this demonstration on a different origin.
 add_action('admin_init', function () { remove_action('admin_init', 'send_frame_options_header'); }, 0);
 remove_action('login_init', 'send_frame_options_header');
}
add_action('init', function () {
 register_post_type('taskflow_task', ['label'=>'Zadania', 'public'=>false, 'show_ui'=>false, 'supports'=>['title','editor']]);
 // Convenience access is explicitly limited to this local demonstration configuration.
 if (defined('TASKFLOW_DEMO') && TASKFLOW_DEMO && !defined('WP_CLI') && !is_user_logged_in()) {
  $user = get_user_by('login','admin');
  if ($user) {
   $expiration = time() + 2 * DAY_IN_SECONDS;
   $token = WP_Session_Tokens::get_instance($user->ID)->create($expiration);
   wp_set_current_user($user->ID);
   wp_set_auth_cookie($user->ID, false, '', $token);
   // The first rendered REST nonce must use the same session as the browser cookie.
   $_COOKIE[LOGGED_IN_COOKIE] = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in', $token);
   // Validate admin access in this request even when iframe cookies are blocked.
   $_COOKIE[AUTH_COOKIE] = wp_generate_auth_cookie($user->ID, $expiration, 'auth', $token);
   $_COOKIE[SECURE_AUTH_COOKIE] = wp_generate_auth_cookie($user->ID, $expiration, 'secure_auth', $token);
  }
 }
});
function taskflow_tasks() {
 return array_map(function($p) {
  $t=['id'=>$p->ID,'title'=>$p->post_title,'description'=>$p->post_content];
  foreach (['project','priority','status','due','assignee'] as $k) $t[$k]=get_post_meta($p->ID,'_tf_'.$k,true);
  return $t;
 }, get_posts(['post_type'=>'taskflow_task','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']));
}
add_action('rest_api_init', function () {
 register_rest_route('taskflow/v1','/tasks', [
  ['methods'=>'GET','callback'=>function(){return taskflow_tasks();},'permission_callback'=>function(){return current_user_can('edit_posts');}],
  ['methods'=>'POST','callback'=>'taskflow_save','permission_callback'=>function(){return current_user_can('edit_posts');}]
 ]);
 register_rest_route('taskflow/v1','/tasks/(?P<id>\d+)', [
  ['methods'=>'PATCH','callback'=>'taskflow_save','permission_callback'=>function(){return current_user_can('edit_posts');}],
  ['methods'=>'DELETE','callback'=>function($r){ if(get_post_type((int)$r['id'])!=='taskflow_task') return new WP_Error('not_found','Nie znaleziono zadania.',['status'=>404]); wp_delete_post((int)$r['id'],true); return ['deleted'=>true]; },'permission_callback'=>function(){return current_user_can('edit_posts');}]
 ]);
}
);
function taskflow_save($r) {
 $data=$r->get_json_params(); $id=(int)($r['id']??0);
 if ($id && get_post_type($id)!=='taskflow_task') return new WP_Error('not_found','Nie znaleziono zadania.',['status'=>404]);
 if (!$id && empty(trim($data['title']??''))) return new WP_Error('invalid','Podaj nazwę zadania.',['status'=>400]);
 $post=['post_type'=>'taskflow_task','post_status'=>'publish'];
 if($id) $post['ID']=$id;
 if(isset($data['title'])) { if(!trim($data['title'])) return new WP_Error('invalid','Podaj nazwę zadania.',['status'=>400]); $post['post_title']=sanitize_text_field($data['title']); }
 if(isset($data['description'])) $post['post_content']=sanitize_textarea_field($data['description']);
 $id=$id ? wp_update_post($post,true) : wp_insert_post($post,true); if(is_wp_error($id)) return $id;
 foreach(['project','priority','status','due','assignee'] as $key) {
  if(!isset($data[$key])) continue;
  $v=sanitize_text_field($data[$key]);
  if($key==='status'&&!in_array($v,['todo','progress','done'])) continue;
  if($key==='priority'&&!in_array($v,['low','medium','high'])) continue;
  if($key==='due'&&$v!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)) continue;
  update_post_meta($id,'_tf_'.$key,$v);
 }
 return array_values(array_filter(taskflow_tasks(),fn($t)=>$t['id']===$id))[0];
}
add_action('admin_menu',function(){
 add_menu_page('Taskflow — Zadania TODO','Taskflow — TODO','edit_posts','taskflow',function(){
  echo '<div class="wrap"><h1>Taskflow — Zadania TODO</h1><p>Zarządzaj zadaniami bezpośrednio w panelu WordPressa. <a class="button" href="/taskflow-demo/" target="_blank" rel="noopener">Przykład headless ↗</a></p>';
  require __DIR__.'/taskflow/view.php';
  echo '</div>';
 },'dashicons-yes-alt',3);
});
add_action('admin_enqueue_scripts',function($hook){
 if($hook!=='toplevel_page_taskflow')return;
 wp_enqueue_style('taskflow-admin','/wp-content/mu-plugins/taskflow/admin.css',[],filemtime(__DIR__.'/taskflow/admin.css'));
});
// Keep native WordPress navigation and assets on the browser's preview origin.
if (!defined('WP_CLI')) {
 foreach(['admin_url','style_loader_src','script_loader_src'] as $filter) {
  add_filter($filter, function($url){return wp_make_link_relative($url);},20);
 }
 add_filter('wp_redirect',function($url){
  $host=parse_url($url,PHP_URL_HOST);
  return $host && $host===parse_url(site_url(),PHP_URL_HOST) ? wp_make_link_relative($url) : $url;
 },20);
}
add_action('template_redirect',function(){
 $path=rtrim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/');
 if($path==='') {wp_safe_redirect('/wp-admin/admin.php?page=taskflow');exit;}
 if($path==='/taskflow-demo') {
  if(!current_user_can('edit_posts')){auth_redirect();exit;}
  require __DIR__.'/taskflow/view.php';exit;
 }
});
