<?php

namespace Crispy\Enums;

enum HrefTargets: string
{
    /**
     * Open in a new tab.
     */
    case BLANK = '_blank'; // Open in a new tab

    /**
     * Open in the same tab.
     */
    case SELF  = '_self'; // Open in the same tab

    /**
     * Open in the parent frame.
     */
    case PARENT = '_parent'; // Open in the parent frame

    /**
     * Open in the full body of the window.
     */
    case TOP = '_top'; // Open in the full body of the window
}
