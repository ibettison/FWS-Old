<?php
class forms {
	var $field_prompt;
	var $field_type;
	var $field_length;
	var $field_value;
	var $field_name;
	var $field_rows;
	var $field_cols;
	var $fieldlist;
	var $form;
	var $form_action;
	var $form_method;
	var $form_title;
	var $form_intro;
	var $field_form;
	var $field_clear;
	var $field_start;
	var $field_end;
	var $field_interval;
	var $field_selected;
	var $field_compare;
	var $field_zindex;
	var $button_value;
	
	function calendars() {
	}
	function create_form($arrFields, $width="150px"){
		//process the array
		foreach( $arrFields as $aField ) {
			$this->field_type = $aField['type'];
			$this->field_prompt = $aField['prompt'];
			$this->field_length = $aField['length'];
			$this->field_rows = $aField['rows'];
			$this->field_cols = $aField['cols'];
			$this->field_value = $aField['value'];
			$this->field_name = $aField['name'];
			$this->field_form = $aField['form'];
			$this->field_clear = $aField['clear'];
			$this->field_start = $aField['starttime'];
			$this->field_end = $aField['endtime'];
			$this->field_interval = $aField["interval"];
			$this->field_list = $aField["listarr"];
			$this->field_link = $aField["link"];
			$this->field_selected = $aField["selected"];
			$this->field_compare = $aField["field"];
			$this->field_multiple = $aField["multiple"];
			$this->field_zindex = $aField["zindex"];
			$this->message = $aField["message"];
			$this->button_value = $aField['buttontext'];
			$this->field_onchange = $aField['onchange'];
			if(!empty($aField['formtitle'])) {
				$this->form_title = $aField['formtitle'];
			}
			if(!empty($aField['formintro'])) {
				$this->form_intro = $aField['formintro'];	
			}
			$this->form .= $this->show_form($this->field_type, $width);
			if(is_array($aField['values'])) {
				//process the values to list in the dropdown/radio/checkbox	option
			}
		}
	}
	function show_form($type, $width) {
		switch($type) {
			case "intro":
				echo ( "<div class='form_intro_title'>$this->form_title</div><div class='form_intro'>$this->form_intro</div>" );
			break;
			case "text":
			?>
				<div class="formPrompt" style="width:<?=$width?>"><?=$this->field_prompt?></div>
                <span class="fieldForm"><input type="<?=$type?>" size="<?=$this->field_length?>" name="<?=$this->field_name?>" value="<?=$this->field_value?>"/></span>
            <?php
			break;
			case "label":
				echo( "<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'>$this->field_value</span>");
			break;
			case "link":
				echo( "<div class='formPrompt' style='width:$width; font-size:9px;'><a href='$this->field_link'>$this->field_prompt</a></div><span class='fieldForm'></span>");
			break;
			case "textcompare":
			?>
				<div class="formPrompt" style="width:<?php echo $width?>"><?php echo $this->field_prompt?></div><span class="fieldForm"><span class="inputArea"><input type="<?php echo $type?>" size="<?php echo $this->field_length?>" name="<?php echo $this->field_name?>" value="<?php echo $this->field_value?>" onblur="check_field('$this->field_compare')" /></span></span>
			<script language="Javascript" type="text/javascript">
                <!--
				function check_field(fld) {
					var check = document.getElementById('<?php echo $this->field_name?>');
					var compare = document.getElementById(fld);
					if(compare.value==check.value) {
						return(true);	
					}else{
						alert('<?php echo $this->message?>');	
					}
				}
				</script>
			<?php
			break;
			case "radio":
				echo ("<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'>");
				foreach($this->field_list as $list) {
					if($this->field_selected == $list) {
						echo "<input type='$type' name='$this->field_name' value='$list' checked/>$list";
					}else{
						echo "<input type='$type' name='$this->field_name' value='$list' />$list";
					}
				}
				echo "</span>";
			break;
			case "date":
				echo "<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'>";
				calendar_picker($this->field_name, "dmY", $this->field_value, $this->field_zindex);
				echo "</span>";
			break;
			case "time":
				//set hour and minutes
				$shour = substr($this->field_start,0,2);
				$sminutes = substr($this->field_start,2,2);	
				$ehour = substr($this->field_end,0,2);
				$eminutes = substr($this->field_end,2,2);				
				echo "<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'>";
				echo "<select name='$this->field_name'>";
				while($shour <= $ehour) {
					if($shour == substr($this->field_selected,0,2)) {
						echo "<option value='".$shour."' selected>";
					}else{
						echo "<option value='".$shour."'>";
					}
					echo $shour++;
					if( $shour < 10 ) {
						$shour = "0".$shour;
					}
					echo "</option>";
				}
				echo "</select>"; //end of first select
				echo " : ";
				echo "<select name='".$this->field_name."_mins'>";
				for($x=0; $x<60; $x+=$this->field_interval){
					if($x < 10) {
						$x = "0".$x;	
					}
					if($x == substr($this->field_selected,3,2)) {
						echo "<option value='$x' selected>";
					}else{
						echo "<option value='$x' >";
					}
					echo "$x";
					echo "</option>";					
				}
				echo "</select></span>";
			break;
			case "textarea":
				echo( "<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'><textarea cols='$this->field_cols' rows='$this->field_rows' name='$this->field_name'>$this->field_value</textarea></span>");
				for($x=1; $x<$this->field_rows; $x++) {
					echo "<br>";
				}
			break;
			case "password":
				echo( "<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'><input type='$type' size='$this->field_length' name='$this->field_name' value='$this->field_value' /></span>");
			break;
			case "passwordcompare":
				echo( "<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'><input type='$type' size='$this->field_length' name='$this->field_name' value='$this->field_value' onblur='check_field(\"$this->field_compare\")' onclick='this.value=\"\"'/></span>");
			?><script language="Javascript" type="text/javascript">
                <!--
				function check_field(fld) {
					var check = document.getElementById('<?php echo $this->field_name?>');
					var compare = document.getElementById(fld);
					if(compare.value==check.value) {
						return(true);	
					}else{
						alert('<?php echo $this->message?>');	
					}
				}
				</script>
			<?php
			break;
			case "note":
				echo "<div class='formLabel'>".$this->field_prompt."</div>";
			break;
			case "checkbox":
				echo( "<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'>");
				if($this->field_selected=="Yes") {
					echo ("<input type='$type' size='$this->field_length' name='$this->field_name' value='$this->field_value' checked /></span>");
				}else{
					echo ("<input type='$type' size='$this->field_length' name='$this->field_name' value='$this->field_value' /></span>");
				}
			break;
			case "selection":
				if(!empty($this->field_list)) {
					echo "<div class='formPrompt' style='width:$width'>$this->field_prompt</div><span class='fieldForm'>";
					if($this->field_multiple) {
						echo "<select name='$this->field_name' multiple size='10' style='width:180px;'>";
					}else{
						if(!empty($this->field_onchange)) {
							?>
							<script language="Javascript" type="text/javascript">
							<!--
							function viewChange(fld) {
								window.location = 'index.php?choice=Add&subchoice=addevent&type='+fld+'&userid=<?php echo $_GET["userid"]?>&date=<?php echo $_GET["date"]?>';	
							}
							</script>
								<select name="<?php echo $this->field_name?>" onchange="viewChange(<?php echo $this->field_onchange?>)">
						<?php 
						}else{
							echo "<select name='$this->field_name'>";
						}
					}
					foreach($this->field_list as $list) {
						if($this->field_multiple){
							if(in_array($list, $this->field_selected)) {
								echo "<option value='$list' selected>$list</option>";
							}else{
								echo "<option value='$list'>$list</option>";
							}
						}else{
							if($list == $this->field_selected) {
								echo "<option value='$list' selected>$list</option>";
							}else{
								echo "<option value='$list'>$list</option>";
							}
						}
					}
					echo "</select></span>";
					if($this->field_multiple) {
						echo "<br><br><br><br><br><br><br><br><br>";
					}
				}
			break;
			case "colour":
				echo "<input type=hidden name='$this->field_name' value='$this->field_value'>";
				echo "<table style='background-color:#f6f6f6;border:1px dotted #666;padding:5px; margin-left:3px;'>";
				echo "<tr><td>Select Colour:<br><small>Click a colour to<br>see your selection listed<br>below the chart</small></td>";
				echo "<td style='border:1px outset #CCF;background-color:#ffe;width=172'>";
				echo "<div id=temoin style='float:right;width:40px;height:128px;'> </div>";
				?>
				<script language="Javascript" type="text/javascript">
                <!--
                var total=1657;var X=Y=j=RG=B=0;
                var aR=new Array(total);var aG=new Array(total);var aB=new Array(total);
                for (var i=0;i<256;i++){
                aR[i+510]=aR[i+765]=aG[i+1020]=aG[i+5*255]=aB[i]=aB[i+255]=0;
                aR[510-i]=aR[i+1020]=aG[i]=aG[1020-i]=aB[i+510]=aB[1530-i]=i;
                aR[i]=aR[1530-i]=aG[i+255]=aG[i+510]=aB[i+765]=aB[i+1020]=255;
                if(i<255){aR[i/2+1530]=127;aG[i/2+1530]=127;aB[i/2+1530]=127;}
                }
                function p(){
					var jla=document.getElementById('choix');
					jla.innerHTML=artabus;
					jla.style.backgroundColor=artabus;
					document.getElementById('<?php echo $this->form_name?>').<?php echo $this->field_name?>.value=artabus;
				}
                var hexbase=new Array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F");
                var i=0;var jl=new Array();
                for(x=0;x<16;x++)for(y=0;y<16;y++)jl[i++]=hexbase[x]+hexbase[y];
                document.write('<'+'table border="0" cellspacing="0" cellpadding="0" onMouseover="t(event)" onClick="p()">');
                var H=W=63;
                for (Y=0;Y<=H;Y++){
                    s='<'+'tr height=2>';j=Math.round(Y*(510/(H+1))-255)
                    for (X=0;X<=W;X++){
                        i=Math.round(X*(total/W))
                        R=aR[i]-j;if(R<0)R=0;if(R>255||isNaN(R))R=255
                        G=aG[i]-j;if(G<0)G=0;if(G>255||isNaN(G))G=255
                        B=aB[i]-j;if(B<0)B=0;if(B>255||isNaN(B))B=255
                        s=s+'<'+'td width=2 bgcolor=#'+jl[R]+jl[G]+jl[B]+'><'+'/td>'
                    }
                    document.write(s+'<'+'/tr>')
                }
                document.write('<'+'/table>');
                var ns6=document.getElementById&&!document.all
                var ie=document.all
                var artabus=''
				function t(e){
                source=ie?event.srcElement:e.target
                if(source.tagName=="TABLE")return
                while(source.tagName!="TD" && source.tagName!="HTML")source=ns6?source.parentNode:source.parentElement
                document.getElementById('temoin').style.backgroundColor=artabus=source.bgColor
                }
                // -->
                </script>
				<div id=choix style='height:24px;' onClick="document.getElementById('<?php echo $this->form_name?>').<?php echo $this->field_name?>.value='';this.style.backgroundColor=''"> </div></td></tr>
				<?php
                echo "</table>";
			break;
			case "submit":
				echo ( "<div class='formSubmit'><input type='submit' value='$this->button_value' /></div>");
			break;
			case "form":
				$this->form_action = $this->field_form['action'];
				$this->form_method = $this->field_form['method'];
				$this->form_name = $this->field_form['formname'];
				echo "<div class='form'><form action='$this->form_action' method='$this->form_method' id='$this->form_name'>";
			break;
			case "endform":
				echo "</form></div>";
			break;
		}
		if($this->field_clear==true) {
			echo "<br><div style='clear:right'>&nbsp;</div>";
		}
	}

}
?>
