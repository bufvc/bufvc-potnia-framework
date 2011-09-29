<? /* Query facet sidebar block */ 
?>
<div class="query-enhancer <?=$this->css_class_name($title)?>">
<h4<?=$help_text == '' ? '' : $help_html = " title=\"" . $help_text ."\" class=\"tip-help\""?>><?=$title?></h4>
<div class="query-enhancer-content">
<? if ($items):
if (!is_array($items))
    $items = Array($items);
?>
<ul>
<? 
$result = Array();
foreach ($items as $item)
    {
    if (is_array($item))
        {
        $value = @$item['value'];
        $css_label = $label = @$item['label'];
        $extra_text = $this->add_extra_text($item);
        $tmp_item = $item;
        if (@$item['url'] != '')
            {
            if ($value != '')
                $value = '<a href="' . $item['url'] . '">' .  $value . '</a>';
            else
                $label = '<a href="' . $item['url'] . '">' .  $label . '</a>';
            }
        $label_end_tag = $value == '' ? '</span>' : ':</span> ';
        $item = '<span class="';
        if ($this->is_selected($tmp_item))
            $item .= 'facet-selected ';
        $item .= 'label">' . $label . $label_end_tag . $value . $extra_text;
        }
    // don't output css class if is a list of links
    $result[] = @$value == '' ? '<li>' . $item . '</li>' : '<li class="' . $this->css_class_name($css_label) . '">' . $item . '</li>';
    }
?>    
<?=join('', $result)?>
</ul>
<? endif /* Items */ ?>
<? if ($description): ?>
<p><?=$description ?></p>
<? endif ?>
</div>
</div>

