<?php

if (!isset($MODAL_CLASS))
    $MODAL_CLASS = "modal-dialog";

if (!isset ($MODAL_TITLE))
    $MODAL_TITLE = "--Missing MODAL_TITLE--";

if (!isset ($MODAL_BODY))
    $MODAL_BODY = "--Missing MODAL_BODY--";

if (!isset ($MODAL_FOOTER))
    $MODAL_FOOTER = "--Missing MODAL_FOOTER--";

return <<<MODAL
<div id="BS-MODAL" class="modal" tabindex="-1" role="dialog">
    <div class="{$MODAL_CLASS}" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$MODAL_TITLE}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                $MODAL_BODY
            </div>
            <div class="modal-footer">
                $MODAL_FOOTER
            </div>
        </div>
    </div>
</div>
MODAL;