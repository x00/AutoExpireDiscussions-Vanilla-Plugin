<?php if (!defined('APPLICATION')) exit();

$PluginInfo['AutoExpireDiscussions'] = array(
   'Name' => 'Auto Expire Discussions',
   'Description' => 'The auto expiry of discussions/questions where there hasn\'t been a comment in N period.',
   'RequiredApplications' => array('Vanilla' => '2.2'),
   'MobileFriendly' => TRUE,
   'Version' => '0.2.0b',
   'Author' => 'Paul Thomas',
   'AuthorEmail' => 'dt01pqt_pt@yahoo.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/x00',
   'SettingsUrl' => '/settings/autoexpire'
);

class AutoExpireDiscussions extends Gdn_Plugin {

    protected $DiscussionListExpire = array();
    protected $CategoriesFull = null;
    
    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = $Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Forum', T('AutoExpireDiscussions.Title','Auto Expire Discussions'), 'settings/autoexpire', 'Garden.Settings.Manage');
    }

    public function SettingsController_AutoExpire_Create($Sender) {
        $Sender->Permission('Garden.Settings.Manage');
        $Sender->Form = Gdn::Factory('Form');
        if($Sender->Form->IsPostBack() != False){
            $Minutes = $Sender->Form->GetValue('AutoExpirePeriod_Minutes');
            $Hours = $Sender->Form->GetValue('AutoExpirePeriod_Hours');
            $Days = $Sender->Form->GetValue('AutoExpirePeriod_Days');
            $Months = $Sender->Form->GetValue('AutoExpirePeriod_Months');
            $Years = $Sender->Form->GetValue('AutoExpirePeriod_Years');
            if(!(empty($Minutes) && empty($Hours) && empty($Days)  && empty($Months) && empty($Years))){
                $Sender->Form->SetFormValue('AutoExpirePeriod',"+{$Years} years {$Months} months {$Days} days {$Hours} hours {$Minutes} minutes");
            }else{
                $Sender->Form->SetFormValue('AutoExpirePeriod',null);
            }
            $FormValues = $Sender->Form->FormValues();
            Gdn::SQL()->Put(
                'Category',
                array('AutoExpirePeriod'=>$FormValues['AutoExpirePeriod']),
                array('CategoryID'=>$FormValues['CategoryID'])
            );
            if(strtolower($FormValues['AutoExpireRetro'])=='yes')
            Gdn::SQL()->Put(
                'Discussion',
                array('AutoExpire'=>1),
                array('CategoryID'=>$FormValues['CategoryID'])
            );
        }

        $CategoryModel = new CategoryModel();
        $CategoryFull = $CategoryModel->GetFull();
        $CatsExpire=array();
        foreach($CategoryFull As $Category){
            $CatsExpire[$Category->CategoryID]= $Category->AutoExpirePeriod;
        }
        $Sender->SetData('CatsExpire',json_encode($CatsExpire));
        $Sender->AddSideMenu();
        $Sender->SetData('Title', T('AutoExpireDiscussions.Title','Auto Expire Discussions'));
        $Sender->SetData('Description',$this->PluginInfo['Description']);
        $Sender->Render('Settings', '', 'plugins/AutoExpireDiscussions');
    }
      
    public function DiscussionController_AutoExpire_Create($Sender, $Args){
        $DiscussionID =intval($Args[0]);
        $DiscussionModel = new DiscussionModel();
        $Discussion = $DiscussionModel->GetID($DiscussionID);
        if(!Gdn::Session()->CheckPermission('Vanilla.Discussions.Close',TRUE, 'Category', $Discussion->PermissionCategoryID)){
            throw PermissionException('Vanilla.Discussions.Close');
        }
        if(strtolower($Args[1])=='reset'){
            Gdn::SQL()->Put(
                'Discussion',
                array('AutoExpire'=>1,'Closed'=>0,'DateReOpened'=>Gdn_Format::ToDateTime()),
                array('DiscussionID'=>$DiscussionID)
            );
        }else{
            $Expire = strtolower($Args[1])=='on'?1:0;
            Gdn::SQL()->Put(
                'Discussion',
                array('AutoExpire'=>$Expire),
                array('DiscussionID'=>$DiscussionID)
            );
        }
        Redirect('discussion/'.$DiscussionID.'/'.Gdn_Format::Url($Discussion->Name));
    }
    
    public function Base_DiscussionOptions_Handler($Sender, &$Args) {
        $Comment = GetValue('Comment', $Args);
        $Discussion = GetValue('Discussion', $Args);
        if (!$Discussion || $Comment || !Gdn::Session()->CheckPermission('Vanilla.Discussions.Close',TRUE, 'Category', $Discussion->PermissionCategoryID))
            return;
            
        $DiscussionID = $Discussion->DiscussionID;
        if($Discussion->Closed && $Discussion->AutoExpire){
            unset($Args['DiscussionOptions']['CloseDiscussion']);
            $Args['DiscussionOptions']['AutoExpireReset'] = array('Label' =>T('AutoExpireDiscussions.AutoExpireReset','AutoExpire Reset'), 'Url' => '/discussion/autoexpire/'.intval($DiscussionID).'/reset', 'class' => 'AutoExpire Reset');
        }elseif($Discussion->AutoExpire) {
            unset($Args['DiscussionOptions']['CloseDiscussion']);
            $Args['DiscussionOptions']['AutoExpireOff'] = array('Label' =>T('AutoExpireDiscussions.AutoExpireOn','AutoExpire (on)'), 'Url' => '/discussion/autoexpire/'.intval($DiscussionID).'/off', 'class' => 'AutoExpire');
        } else {
            $Args['DiscussionOptions']['AutoExpireOn'] = array('Label' =>T('AutoExpireDiscussions.AutoExpireOn','AutoExpire (off)'), 'Url' => '/discussion/autoexpire/'.intval($DiscussionID).'/on', 'class' => 'AutoExpire');
        }
    }
    
    public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender,&$Args){
        $FormVars = &$Args['FormPostValues'];
        $CategoryID = $FormVars['CategoryID'];
        $CategoryModel = new CategoryModel();
        $Category = $CategoryModel->GetFull($CategoryID);
        if(!$Category->AutoExpirePeriod)
            return;
        if(!array_key_exists('AutoExpire',$FormVars) || !Gdn::Session()->CheckPermission('Vanilla.Discussions.Close',TRUE, 'Category', $Category->PermissionCategoryID))
            $FormVars['AutoExpire']=TRUE;
    }
    
    public function ExpireCheck($Discussion){
        if($Discussion->DiscussionID && !$Discussion->Closed && $Discussion->AutoExpire){
            if(empty($this->CategoriesFull)){
                $CategoryModel = new CategoryModel();
                $this->CategoriesFull = $CategoryModel->GetFull();
            }
            foreach($this->CategoriesFull As $Cat){
                if($Cat->CategoryID==$Discussion->CategoryID){
                $Category = $Cat;
                break;
                }
            }
            $DateReOpened = strtotime($Discussion->DateReOpened);
            $DateLastComment = strtotime($Discussion->DateLastComment);
            $DateLast = $DateLastComment>$DateReOpened?$DateLastComment:$DateReOpened;
            $AutoExpirePeriod = strtotime($Category->AutoExpirePeriod)-time();
            if($DateLast+$AutoExpirePeriod<time()){
                $Discussion->Closed=1;
                $this->DiscussionListExpire[] = $Discussion->DiscussionID;
              
            }
        }
    }
    
    public function Expire(){
        if(!empty($this->DiscussionListExpire))
            Gdn::SQL()->Put(
                'Discussion',
                array('AutoExpire'=>1,'Closed'=>1,'DateReOpened'=>null),
                array('DiscussionID'=>$this->DiscussionListExpire)
            );
    }
    
    public function DiscussionsController_Render_Before($Sender){
        foreach($Sender->Discussions As $Discussion){
            $this->ExpireCheck($Discussion);
        }
        $this->Expire();
    }
    
    public function DiscussionController_Render_Before($Sender){
        $this->ExpireCheck($Sender->Data('Discussion'));
        $this->Expire();

        if(Gdn::Session()->CheckPermission('Vanilla.Discussions.Close',TRUE, 'Category', $Sender->Data('Discussion')->PermissionCategoryID) && $Sender->Data('Discussion')->Closed && $Sender->Data('Discussion')->AutoExpire){
            $Sender->AddJsFile('autoexpire.js','plugins/AutoExpireDiscussions');
        }
        if($Sender->Data('Discussion')->Closed && $Sender->Data('Discussion')->AutoExpire){
            Gdn::Locale()->SetTranslation('This discussion has been closed.', 'This discussion has expired.');
        }
    }
    
    public function Base_BeforeDiscussionMeta_Handler($Sender, &$Args){
        $Discussion = $Args['Discussion'];
        if(($Discussion->Closed && $Discussion->AutoExpire) || in_array($Discussion->DiscussionID,$this->DiscussionListExpire)){
            echo  '<span class="Tag Tag-Expired">'.T('AutoExpireDiscussions.Expired','Expired').'</span>';
            $Discussion->Closed=0;
        }
    }
    
    public function PostController_Render_Before($Sender){
        if(Gdn::Session()->CheckPermission('Vanilla.Discussions.Close')){
            $CategoryModel = new CategoryModel();
            $CategoryFull = $CategoryModel->GetFull();
            $CatsExpire=array();
            foreach($CategoryFull As $Category){
                $CatsExpire[$Category->CategoryID]=Gdn::Session()->CheckPermission('Vanilla.Discussions.Close',TRUE, 'Category', $Category->PermissionCategoryID) && $Category->AutoExpirePeriod;
            }
            $Sender->AddDefinition('CatsExpire',json_encode($CatsExpire));
            $Sender->AddJsFile('autoexpirecheck.js','plugins/AutoExpireDiscussions');
        }
    }

    public function Base_DiscussionFormOptions_Handler($Sender, &$Args){
        $DefaultExpire = FALSE;
        if(!$Sender->Form->HiddenInputs['DiscussionID'])
            $DefaultExpire = C('Plugins.AutoExpireDiscussions.AdminDefaultExpire',TRUE);
        if(Gdn::Session()->CheckPermission('Vanilla.Discussions.Close')){
            $Args['Options'].='<li>'.$Sender->Form->CheckBox('AutoExpire', T('AutoExpireDiscussions.AutoExpire','Auto Expire'), $DefaultExpire?array('value' => '1','checked'=>'checked'):array('value' => '1')).'</li>';
        }
    }
    
    public function Setup() {
        $this->Structure();
    }
      
    public function Base_BeforeDispatch_Handler($Sender){
        if(C('Plugins.AutoExpireDiscussions.Version')!=$this->PluginInfo['Version'])
            $this->Structure();
    }
      
    public function Structure() {
      
        Gdn::Structure()
            ->Table('Category')
            ->Column('AutoExpirePeriod','varchar(150)',NULL)
            ->Set();
        Gdn::Structure()
            ->Table('Discussion')
            ->Column('AutoExpire','int(4)',0)
            ->Column('DateReOpened','datetime',NULL)
            ->Set();
              
        //Save Version for hot update
      
        SaveToConfig('Plugins.AutoExpireDiscussions.Version', $this->PluginInfo['Version']);
    }

}
