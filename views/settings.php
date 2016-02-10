<?php if (!defined('APPLICATION')) exit(); ?>
<style>
fieldset{
border:1px solid #CCCCCC;
width:580px;
padding:5px;
margin-bottom:6px;
}

label{
display:block;
}
label.Inline{
display:inline;
}

select.Empty, option[value="0"]{
color:grey;
}

select option{
color:black;
}

</style>
<script type="text/javascript">
jQuery(document).ready(function($){
    var catsExp = $.parseJSON($('.CatsExpire').val());
    $('.CategoryDropDown').on('change keypress',function(){
        var period = catsExp[$('.CategoryDropDown').val()] || '';
                
        var periodParts = period.match(/\+\s*(\d+)\s+years\s+(\d+)\s+months\s+(\d+)\s+days\s+(\d+)\s+hours\s+(\d+)\s+minutes/i);
        $('#Form_AutoExpirePeriod_Years option').removeAttr('selected');
        $('#Form_AutoExpirePeriod_Months option').removeAttr('selected');
        $('#Form_AutoExpirePeriod_Hours option').removeAttr('selected');
        $('#Form_AutoExpirePeriod_Minutes option').removeAttr('selected');
        
        if(!periodParts || !periodParts.length){
            periodParts = [0,0,0,0,0,0];
        }
            
        $('#Form_AutoExpirePeriod_Years option[value="'+parseInt(periodParts[1])+'"]').attr('selected','selected');
        $('#Form_AutoExpirePeriod_Years').val(parseInt(periodParts[1]));
        $('#Form_AutoExpirePeriod_Months option[value="'+parseInt(periodParts[2])+'"]').attr('selected','selected');
        $('#Form_AutoExpirePeriod_Months').val(parseInt(periodParts[2]));
        $('#Form_AutoExpirePeriod_Days option[value="'+parseInt(periodParts[3])+'"]').attr('selected','selected');
        $('#Form_AutoExpirePeriod_Days').val(parseInt(periodParts[3]));
        $('#Form_AutoExpirePeriod_Hours option[value="'+parseInt(periodParts[4])+'"]').attr('selected','selected');
        $('#Form_AutoExpirePeriod_Hours').val(parseInt(periodParts[4]));
        $('#Form_AutoExpirePeriod_Minutes option[value="'+parseInt(periodParts[5])+'"]').attr('selected','selected');
        $('#Form_AutoExpirePeriod_Minutes').val(parseInt(periodParts[5]));
        
        $('select.AutoExpire').trigger('change');
        
        $('#Form_AutoExpireRetro option').removeAttr('selected');
        $('#Form_AutoExpireRetro option[value="don\'t"]').attr('selected','selected');
        
    });
    $('select.AutoExpire').bind('change',function(){
            if($(this).val()==0)
                $(this).addClass('Empty');
            else
                $(this).removeClass('Empty');
    });
    $('.CategoryDropDown').trigger('change');
    
});
</script>
<h1><?php echo $this->Data['Title'] ?></h1>
<div class="Info">
    <?php echo $this->Data['Description'] ?>
</div>
<div>
<?php
    echo $this->Form->Open();
    echo $this->Form->Errors();
?>
<div class="Configuration">
<div class="ConfigurationForm">
    <ul>
        <li>
        <?php
            echo $this->Form->Hidden('CatsExpire',array('class'=>'CatsExpire','value'=>$this->Data['CatsExpire']));
            echo $this->Form->Label(T('AutoExpireDiscussions.Category','Category'), 'CategoryID');
            echo $this->Form->CategoryDropDown('CategoryID',array('class'=>'CategoryDropDown'));
        ?>
        </li>
        <li>
            <fieldset>
                <legend><?php echo T('AutoExpireDiscussions.ExpiryPeriod','Expiry Period') ?></legend>
                    <?php
                    echo $this->Form->DropDown('AutoExpirePeriod_Minutes', range(0,60),array('class'=>'AutoExpire'));
                    echo $this->Form->Label(T('AutoExpireDiscussions.Minutes','Minute(s)'), 'AutoExpirePeriod_Minutes',array('class'=>'Inline'));
                    echo $this->Form->DropDown('AutoExpirePeriod_Hours', range(0,24),array('class'=>'AutoExpire'));
                    echo $this->Form->Label(T('AutoExpireDiscussions.Hours','Hour(s)'), 'AutoExpirePeriod_Hours',array('class'=>'Inline'));
                    echo $this->Form->DropDown('AutoExpirePeriod_Days', range(0,31),array('class'=>'AutoExpire'));
                    echo $this->Form->Label(T('AutoExpireDiscussions.Days','Day(s)'), 'AutoExpirePeriod_Days',array('class'=>'Inline'));
                    echo $this->Form->DropDown('AutoExpirePeriod_Months', range(0,12),array('class'=>'AutoExpire'));
                    echo $this->Form->Label(T('AutoExpireDiscussions.Months','Month(s)'), 'AutoExpirePeriod_Months',array('class'=>'Inline'));
                    echo $this->Form->DropDown('AutoExpirePeriod_Years', range(0,10),array('class'=>'AutoExpire'));
                    echo $this->Form->Label(T('AutoExpireDiscussions.Years','Year(s)'), 'AutoExpirePeriod_Years',array('class'=>'Inline'));
                    ?>
            </fieldset>
        </li>
        <li>
        <?php 
            echo $this->Form->Label(T('AutoExpireDiscussions.SetAutoExpireAll','Set auto expire all posts in this category, use wisely!'),'AutoExpireRetro'); 
            echo $this->Form->DropDown('AutoExpireRetro', array('don\'t'=>T('AutoExpireDiscussions.Warning','Don\'t even think about it!'),'no'=>T('AutoExpireDiscussions.No','no'),'yes'=>T('AutoExpireDiscussions.Yes','yes')),array('class'=>'AutoExpire'));
        ?>
        </li>
        <li>
            <?php echo $this->Form->Button(T('AutoExpireDiscussions.Save','Save'), array('class' => 'SmallButton SliceSubmit')); ?>
        </li>
    </ul>
</div>
</div>
    <?php
    echo $this->Form->Close();
    ?>
</div>
