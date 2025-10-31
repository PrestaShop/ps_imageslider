{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}

{extends file="helpers/form/form.tpl"}
{block name="field"}
	{if $input.type == 'file_lang'}
		<div class="col-lg-8">
			<div class="form-group">
				{foreach from=$languages item=language}
					{if $languages|count > 1}
						<div class="translatable-field lang-{$language.id_lang}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
					{/if}
						<div class="col-lg-{if $languages|count > 1}10{else}12{/if}">
							{if isset($fields[0]['form']['images'])}
								{assign var=file_type value="."|explode:$fields[0]['form']['images'][$language.id_lang]|@end}
								{if $file_type|in_array:['mp4']}
									{assign var=poster value=$fields[0]['form']['poster'][$language.id_lang]}
									<video class="img-thumbnail" {if $poster}poster="{$image_baseurl}{$poster}"{/if} controls autoplay playsinline muted>
										<source type="video/mp4" src="{$image_baseurl}{$fields[0]['form']['images'][$language.id_lang]}" alt="{$slide.title}"/>
									</video>
								{else}
									<img src="{$image_baseurl}{$fields[0]['form']['images'][$language.id_lang]}" class="img-thumbnail" />
								{/if}
							{/if}
							<div class="dummyfile input-group">
								<input id="{$input.name}_{$language.id_lang}" type="file" name="{$input.name}_{$language.id_lang}" class="hide-file-upload" />
								<span class="input-group-addon"><i class="icon-file"></i></span>
								<input id="{$input.name}_{$language.id_lang}-name" type="text" class="disabled" name="filename" readonly />
								<span class="input-group-btn">
									<button id="{$input.name}_{$language.id_lang}-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
										<i class="icon-folder-open"></i> {l s='Choose a file' d='Admin.Actions'}
									</button>
								</span>
							</div>
						</div>
					{if $languages|count > 1}
						<div class="col-lg-2">
							<button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
								{$language.iso_code}
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								{foreach from=$languages item=lang}
								<li><a href="javascript:hideOtherLanguage({$lang.id_lang});" tabindex="-1">{$lang.name}</a></li>
								{/foreach}
							</ul>
						</div>
					{/if}
					{if $languages|count > 1}
						</div>
					{/if}
					<script>
					$(document).ready(function(){
						$('#{$input.name}_{$language.id_lang}-selectbutton').click(function(e){
							$('#{$input.name}_{$language.id_lang}').trigger('click');
						});
						$('#{$input.name}_{$language.id_lang}').change(function(e){
							var val = $(this).val();
							var file = val.split(/[\\/]/);
							$('#{$input.name}_{$language.id_lang}-name').val(file[file.length-1]);
						});
					});
				  </script>
				{/foreach}
			</div>
		</div>
	{/if}
	{$smarty.block.parent}
{/block}
