<?php
/**
 * 进退群提示 + 撤回 管理插件
 */

// ===== 存储消息 ID =====
if (in_array($this->事件类型 ?? '', ['GROUP_MESSAGE_CREATE', 'GROUP_AT_MESSAGE_CREATE'])) {
    if (!empty($this->来源ID) && !empty($this->用户ID) && !empty($this->信息ID)) {
        $msgs = $this->数据库('读', "msgids/{$this->来源ID}/{$this->用户ID}") ?: [];
        $msgs[] = ['id' => $this->信息ID, 'ts' => time()];
        if (count($msgs) > 50) array_shift($msgs);
        $this->数据库('写', "msgids/{$this->来源ID}/{$this->用户ID}", $msgs);
    }
}

// ===== 关键词自动撤回 =====
if (in_array($this->事件类型 ?? '', ['GROUP_MESSAGE_CREATE', 'GROUP_AT_MESSAGE_CREATE'])) {
    $content = trim($this->用户信息 ?? '');
    if ($content !== '') {
        $list = $this->数据库('读', "keywordrecall/{$this->来源ID}") ?? [];
        if (is_array($list) && !empty($list)) {
            foreach ($list as $kw) {
                if (mb_strpos($content, $kw) !== false) {
                    if (!empty($this->信息ID)) { $this->撤回($this->信息ID); }
                    break;
                }
            }
        }
    }
}

// ===== 媒体自动撤回 =====
if (in_array($this->事件类型 ?? '', ['GROUP_MESSAGE_CREATE', 'GROUP_AT_MESSAGE_CREATE'])) {
    $content = trim($this->用户信息 ?? '');
    if ($content === '') {
        $raw = $this->消息原始数据 ?? [];
        $mediaType = '';
        if (!empty($raw['media']['media_type'])) {
            $map = [1 => 'image', 2 => 'video', 3 => 'voice', 4 => 'file'];
            $mediaType = $map[$raw['media']['media_type']] ?? '';
        } elseif (!empty($raw['attachments'][0]['content_type'])) {
            $ct = $raw['attachments'][0]['content_type'];
            if (strpos($ct, 'image/') === 0) $mediaType = 'image';
            elseif (strpos($ct, 'video/') === 0) $mediaType = 'video';
            elseif (strpos($ct, 'audio/') === 0) $mediaType = 'voice';
            else $mediaType = 'file';
        }
        if ($mediaType) {
            $set = $this->数据库('读', "recallsettings/{$this->来源ID}") ?: [];
            if (!empty($set[$mediaType]) && !empty($this->信息ID)) { $this->撤回($this->信息ID); }
        }
    }
}

// ===== 链接自动撤回 =====
if (in_array($this->事件类型 ?? '', ['GROUP_MESSAGE_CREATE', 'GROUP_AT_MESSAGE_CREATE'])) {
    $set = $this->数据库('读', "recallsettings/{$this->来源ID}") ?: [];
    if (!empty($set['link'])) {
        $content = trim($this->用户信息 ?? '');
        $raw = $this->消息原始数据 ?? [];
        $hasLink = false;
        if (strpos($content, 'http://') !== false || strpos($content, 'https://') !== false) $hasLink = true;
        if (!$hasLink && preg_match('/[a-zA-Z0-9\-]+\.[a-zA-Z]{2,}/', $content)) $hasLink = true;
        if (!empty($raw['ark_data'])) $hasLink = true;
        if ($hasLink && !empty($this->信息ID)) { $this->撤回($this->信息ID); }
    }
}

// ===== 名片自动撤回 =====
if (in_array($this->事件类型 ?? '', ['GROUP_MESSAGE_CREATE', 'GROUP_AT_MESSAGE_CREATE'])) {
    $set = $this->数据库('读', "recallsettings/{$this->来源ID}") ?: [];
    if (!empty($set['card'])) {
        $raw = $this->消息原始数据 ?? [];
        if (!empty($raw['message_type']) && $raw['message_type'] == 3 && !empty($raw['ark_data'])) {
            if (!empty($this->信息ID)) { $this->撤回($this->信息ID); }
        }
    }
}

// ===== 辅助函数 =====
if (!function_exists('_btn')) {
function _btn($id, $label, $style, $data) {
    return ['id'=>$id,'render_data'=>['label'=>$label,'style'=>$style],
        'action'=>['type'=>1,'permission'=>['type'=>1],'data'=>$data]];
}
}
if (!function_exists('_管理面板')) {
function _管理面板() {
    return ['style'=>['font_size'=>'small'],'rows'=>[['buttons'=>[
        _btn('btn_jcq','📋 进退群管理',4,'进退群管理'),
        _btn('btn_recall','🗑️ 撤回管理',4,'撤回管理'),
    ]]]];
}
}
if (!function_exists('_进退群面板')) {
function _进退群面板($set) {
    $j=($set['join']??false)?'✅':'❎';$t=($set['leave']??false)?'✅':'❎';
    return ['style'=>['font_size'=>'small'],'rows'=>[
        ['buttons'=>[_btn('btn_join_on',"{$j} 开启进群提示",1,'开启进群提示'),_btn('btn_join_off',"{$j} 关闭进群提示",1,'关闭进群提示')]],
        ['buttons'=>[_btn('btn_leave_on',"{$t} 开启退群提示",1,'开启退群提示'),_btn('btn_leave_off',"{$t} 关闭退群提示",1,'关闭退群提示')]],
        ['buttons'=>[_btn('btn_back','🔙 返回',3,'返回管理')]]
    ]];
}
}
if (!function_exists('_撤回面板')) {
function _撤回面板($kwList,$recallSet=[]) {
    $rows=[];$img=!empty($recallSet['image'])?'✅':'❎';$vid=!empty($recallSet['video'])?'✅':'❎';
    $voc=!empty($recallSet['voice'])?'✅':'❎';$fil=!empty($recallSet['file'])?'✅':'❎';
    $lk=!empty($recallSet['link'])?'✅':'❎';$cd=!empty($recallSet['card'])?'✅':'❎';
    if(is_array($kwList)&&!empty($kwList)){$row=[];foreach($kwList as $i=>$kw){$row[]=_btn("kw_{$i}","✕ {$kw}",3,"删撤回 {$kw}");if(count($row)>=3){$rows[]=['buttons'=>$row];$row=[];}}if(!empty($row))$rows[]=['buttons'=>$row];}
    $rows[]=['buttons'=>[_btn('btn_img',"{$img} 图片撤回",1,'切换图片撤回'),_btn('btn_video',"{$vid} 视频撤回",1,'切换视频撤回')]];
    $rows[]=['buttons'=>[_btn('btn_voice',"{$voc} 语音撤回",1,'切换语音撤回'),_btn('btn_file',"{$fil} 文件撤回",1,'切换文件撤回')]];
    $rows[]=['buttons'=>[_btn('btn_link',"{$lk} 链接撤回",1,'切换链接撤回'),_btn('btn_card',"{$cd} 名片撤回",1,'切换名片撤回')]];
    $rows[]=['buttons'=>[_btn('btn_back','🔙 返回',3,'返回管理')]];
    return ['style'=>['font_size'=>'small'],'rows'=>$rows];
}
}
// ===== 回调按钮 =====
if (!empty($this->按钮数据) && !empty($this->按钮ID)) {
    $data = $this->按钮数据;
    if ($data === '返回管理') {
        $this->发送('md',null,'📋 **群聊管理中心**'."\n---\n请选择要管理的功能",_管理面板());return;
    }
    if ($data === '进退群管理') {
        $set=$this->数据库('读',"eventsettings/{$this->来源ID}")?:[];
        $this->发送('md',null,'📋 **进退群提示管理**'."\n---\n👋 入群：".(($set['join']??false)?'✅开启':'❎关闭')."\n🚪 退群：".(($set['leave']??false)?'✅开启':'❎关闭'),_进退群面板($set));return;
    }
    if ($data === '撤回管理') {
        $kwList=$this->数据库('读',"keywordrecall/{$this->来源ID}")??[];$recallSet=$this->数据库('读',"recallsettings/{$this->来源ID}")?:[];
        $kwC=is_array($kwList)?count($kwList):0;$img=!empty($recallSet['image'])?'✅':'❎';$vid=!empty($recallSet['video'])?'✅':'❎';
        $voc=!empty($recallSet['voice'])?'✅':'❎';$fil=!empty($recallSet['file'])?'✅':'❎';$lk=!empty($recallSet['link'])?'✅':'❎';$cd=!empty($recallSet['card'])?'✅':'❎';
        $msg='🗑️ **撤回管理**';
        if($kwC>0){$msg.="\n---\n**关键词**（{$kwC}个）";foreach($kwList as $i=>$kw){$msg.="\n".($i+1).". 「{$kw}」";}}
        $msg.="\n---\n**自动撤回**\n| 类型 | 状态 |\n| --- | --- |";
        $msg.="\n| 🖼️ 图片撤回 | {$img} |\n| 🎬 视频撤回 | {$vid} |\n| 🎵 语音撤回 | {$voc} |";
        $msg.="\n| 📎 文件撤回 | {$fil} |\n| 🔗 链接撤回 | {$lk} |\n| 🪪 名片撤回 | {$cd} |";
        $msg.="\n\n点击下方按钮切换";
        $this->发送('md',null,$msg,_撤回面板($kwList,$recallSet));return;
    }
    // 进退群 toggle
    $toggleMap=['开启进群提示'=>'join','关闭进群提示'=>'join','开启退群提示'=>'leave','关闭退群提示'=>'leave'];
    if(isset($toggleMap[$data])){
        $key=$toggleMap[$data];$val=in_array($data,['开启进群提示','开启退群提示']);
        $this->数据库('写',"eventsettings/{$this->来源ID}",array_merge($this->数据库('读',"eventsettings/{$this->来源ID}")?:[],[$key=>$val]));
        $this->发送('md',null,'✅ 已'.($val?'开启':'关闭').($key==='join'?'入群':'退群').'提示',_进退群面板($this->数据库('读',"eventsettings/{$this->来源ID}")?:[]));return;
    }
    // 撤回 toggle
    $recallToggle=['切换图片撤回'=>'image','切换视频撤回'=>'video','切换语音撤回'=>'voice','切换文件撤回'=>'file','切换链接撤回'=>'link','切换名片撤回'=>'card'];
    if(isset($recallToggle[$data])){
        $key=$recallToggle[$data];$set=$this->数据库('读',"recallsettings/{$this->来源ID}")?:[];
        $set[$key]=empty($set[$key])?true:false;$this->数据库('写',"recallsettings/{$this->来源ID}",$set);
        $kwList=$this->数据库('读',"keywordrecall/{$this->来源ID}")??[];
        $label=['image'=>'图片','video'=>'视频','voice'=>'语音','file'=>'文件','link'=>'链接','card'=>'名片'][$key];
        $this->发送('md',null,'✅ 已'.($set[$key]?'开启':'关闭').$label.'撤回',_撤回面板($kwList,$set));return;
    }
    // 删关键词
    if(preg_match('/^删撤回\s+(.+)$/u',$data,$m)){
        $kw=trim($m[1]);$list=$this->数据库('读',"keywordrecall/{$this->来源ID}")??[];
        if(!is_array($list))$list=[];$idx=array_search($kw,$list);
        if($idx!==false){array_splice($list,$idx,1);$this->数据库('写',"keywordrecall/{$this->来源ID}",$list);}
        $recallSet=$this->数据库('读',"recallsettings/{$this->来源ID}")?:[];$left=count($list);
        $this->发送('md',null,"✅ 已删除关键词「{$kw}」".($left>0?"\n剩余 {$left} 个":"\n📭 已没有关键词"),_撤回面板($list,$recallSet));return;
    }
}

// ===== 手动撤回 =====
$isRecallCmd=false;$targetId='';$recallCount=1;
if(in_array($this->事件类型??'',['GROUP_AT_MESSAGE_CREATE','GROUP_MESSAGE_CREATE'])){
    $c=trim($this->用户信息??'');
    if(preg_match('/撤回/i',$c)){
        if($this->事件类型==='GROUP_MESSAGE_CREATE'&&!empty($this->艾特用户)){
            $targetId=strtoupper($this->艾特用户);preg_match('/(\d+)$/',$c,$cm);$recallCount=max(1,min(intval($cm[1]??1),10));$isRecallCmd=true;
        }elseif($this->事件类型==='GROUP_AT_MESSAGE_CREATE'){
            preg_match_all('/<@([A-F0-9]+)>/i',$c,$atMentions);
            if(!empty($atMentions[1])){$targetId=strtoupper(end($atMentions[1]));preg_match('/(\d+)$/',$c,$cm);$recallCount=max(1,min(intval($cm[1]??1),10));$isRecallCmd=true;}
        }
    }
}
if($isRecallCmd&&$targetId){
    $msgs=$this->数据库('读',"msgids/{$this->来源ID}/{$targetId}")?:[];if(empty($msgs)){$this->发送('md', null,'❎ 无消息可撤回');return;}
    $toRecall=array_slice($msgs,-$recallCount);$suc=0;$fail=0;
    foreach($toRecall as $m){if($this->撤回($m['id']))$suc++;else$fail++;usleep(100000);}
    $msgs=array_slice($msgs,0,-$recallCount);
    if(!empty($msgs))$this->数据库('写',"msgids/{$this->来源ID}/{$targetId}",$msgs);else$this->数据库('删',"msgids/{$this->来源ID}/{$targetId}");
    $this->发送('md', null,"✅ 已撤回 {$suc} 条".($fail>0?"，{$fail} 条失败":''));return;
}

// ===== 进退群事件 =====
if($this->事件类型==='GROUP_MEMBER_ADD'){
    $set=$this->数据库('读',"eventsettings/{$this->来源ID}");
    if($set&&!empty($set['join'])){
        $this->发送('md',null,"✨ 欢迎 <qqbot-at-user id=\"{$this->用户ID}\" /> 来到本群\n🕒 ".date('Y-m-d H:i:s'));
    }
    return;
}
if($this->事件类型==='GROUP_MEMBER_REMOVE'){
    $set=$this->数据库('读',"eventsettings/{$this->来源ID}");
    if($set&&!empty($set['leave'])){
        $this->发送('md',null,"👋 有人退群啦\n🕐 ".date('Y-m-d H:i:s'));
    }
    return;
}

// ===== 文本指令 =====
if(!in_array($this->事件类型??'',['GROUP_AT_MESSAGE_CREATE','GROUP_MESSAGE_CREATE']))return;
$msg=trim($this->用户信息??'');if(empty($msg))return;
if($msg==='管理'||$msg==='群聊管理'){
    $this->发送('md',null,'📋 **群聊管理中心**'."\n---\n请选择要管理的功能",_管理面板());return;
}
if(preg_match('/^加撤回\s+(.+)$/u',$msg,$m)){
    if(!$this->是管理员()){$this->发送('md', null,'❎ 权限不足');return;}
    $kw=trim($m[1]);if(empty($kw))return;
    $list=$this->数据库('读',"keywordrecall/{$this->来源ID}")??[];
    if(!is_array($list))$list=[];if(in_array($kw,$list)){$this->发送('md', null,"⚠️ 关键词「{$kw}」已存在");return;}
    $list[]=$kw;$this->数据库('写',"keywordrecall/{$this->来源ID}",$list);
    $this->发送('md', null,"✅ 已添加撤回关键词「{$kw}」\n当前共 ".count($list)." 个");return;
}
if(preg_match('/^删撤回\s+(.+)$/u',$msg,$m)){
    if(!$this->是管理员()){$this->发送('md', null,'❎ 权限不足');return;}
    $kw=trim($m[1]);if(empty($kw))return;
    $list=$this->数据库('读',"keywordrecall/{$this->来源ID}")??[];
    if(!is_array($list))$list=[];$idx=array_search($kw,$list);
    if($idx===false){$this->发送('md', null,"⚠️ 关键词「{$kw}」不存在");return;}
    array_splice($list,$idx,1);$this->数据库('写',"keywordrecall/{$this->来源ID}",$list);
    $this->发送('md', null,"✅ 已删除撤回关键词「{$kw}」");return;
}
if($msg==='查看撤回'){
    $list=$this->数据库('读',"keywordrecall/{$this->来源ID}")??[];
    if(empty($list)||!is_array($list)){$this->发送('md', null,'📋 暂无撤回关键词');return;}
    $txt="📋 撤回关键词（共 ".count($list)." 个）：\n";foreach($list as $i=>$kw){$txt.=($i+1).". 「{$kw}」\n";}
    $this->发送('md', null,$txt);return;
}
switch($msg){
    case '开启进群提示':$this->数据库('写',"eventsettings/{$this->来源ID}",array_merge($this->数据库('读',"eventsettings/{$this->来源ID}")?:[],['join'=>true]));$this->发送('md', null,'✅ 已开启入群提示');break;
    case '关闭进群提示':$this->数据库('写',"eventsettings/{$this->来源ID}",array_merge($this->数据库('读',"eventsettings/{$this->来源ID}")?:[],['join'=>false]));$this->发送('md', null,'✅ 已关闭入群提示');break;
    case '开启退群提示':$this->数据库('写',"eventsettings/{$this->来源ID}",array_merge($this->数据库('读',"eventsettings/{$this->来源ID}")?:[],['leave'=>true]));$this->发送('md', null,'✅ 已开启退群提示');break;
    case '关闭退群提示':$this->数据库('写',"eventsettings/{$this->来源ID}",array_merge($this->数据库('读',"eventsettings/{$this->来源ID}")?:[],['leave'=>false]));$this->发送('md', null,'✅ 已关闭退群提示');break;
    case '进退群状态':$set=$this->数据库('读',"eventsettings/{$this->来源ID}")?:[];$this->发送('md', null,"📋 进退群提示状态\n---\n👋 入群：".(($set['join']??false)?'✅开启':'❎关闭')."\n🚪 退群：".(($set['leave']??false)?'✅开启':'❎关闭'));break;
}
