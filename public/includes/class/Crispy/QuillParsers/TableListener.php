<?php

namespace Crispy\QuillParsers;

use nadar\quill\BlockListener;
use nadar\quill\Line;
use nadar\quill\Lexer;
use nadar\quill\Pick;

class TableListener extends BlockListener
{

    protected $col_widths = []; //collect widths for table cols

    protected $col_alignments = []; // Collect alignments for table cols


    /**
     * Process a line in the delta.
     * @param Line $line
     */
    public function process(Line $line)
    {
        if ($line->getAttribute('table-cell-line')) {
            $this->pick($line); // Pick the cell line
            $line->setDone();

            //when current line is detected as table cell, then the previous line is the content of this cell.
            $content_line = $line->getLexer()->getLine($line->getIndex() - 1); //get previous line



            $content_line->setDone(); //mark as done

        } elseif ($line->getAttribute('table-col')) {

            $this->col_widths[] = $line->getAttribute('table-col')['width']; //collect the width of each column
            $this->col_alignments[] = $line->getAttribute('align') ?? 'left'; // Default alignment to left if not specified


            $this->pick($line); // Pick the column
            $line->setDone();
        }
    }

    /**
     * Render the HTML output for the picked lines.
     * @param Lexer $lexer
     */
    public function render(Lexer $lexer)
    {
        $tableHtml = '';
        $rowHtml = '';
        $prevRow = null;
        $colIndex = 0; // Initialize column index


        foreach ($this->picks() as $pick) {
            $line = $pick->line;

            if ($line->getAttribute('table-col') && empty($tableHtml)) {
                // Start table if not already started
                $tableHtml = '<table class="quill-table">' . PHP_EOL;
            }

            $cellAttributes = $line->getAttribute('table-cell-line');
            if ($cellAttributes) {
                $currentRow = $cellAttributes['row'];

                if ($prevRow !== $currentRow) {
                    if ($prevRow !== null) {
                        // Close previous row and append to table HTML
                        $tableHtml .= $rowHtml . '</tr>' . PHP_EOL;
                        $rowHtml = ''; // Reset row HTML
                        $colIndex = 0; // Reset column index for the new row
                    }
                    $rowHtml .= '<tr>' . PHP_EOL; // Start new row
                }

                $content_line = $line->getLexer()->getLine($line->getIndex() - 1);
                $attributesString = $this->prepareAttributes($cellAttributes);

                // Apply the column width for the current cell
                if (array_key_exists($colIndex, $this->col_widths)) {

                    $widthStyle = isset($this->col_widths[$colIndex]) ? "width: {$this->col_widths[$colIndex]}px;" : '';
                    $alignmentStyle = isset($this->col_alignments[$colIndex]) ? "text-align: {$this->col_alignments[$colIndex]};" : '';

                    $rowHtml .= "<td style='{$widthStyle} {$alignmentStyle}'{$attributesString}>" . $content_line->getInput() . '</td>' . PHP_EOL;
                    $colIndex++;
                } else {
                    // If the column index exceeds the available widths, just add the cell without a width style
                    $rowHtml .= "<td{$attributesString}>" . $content_line->getInput() . '</td>' . PHP_EOL;
                }

                $prevRow = $currentRow;
            }
        }

        if (!empty($rowHtml)) {
            // Close last row if exists
            $tableHtml .= $rowHtml . '</tr>' . PHP_EOL;
        }

        if (!empty($tableHtml)) {
            // Close the table tag
            $tableHtml .= '</table>' . PHP_EOL;
        }


        // Assign the final HTML to the last pick's output to ensure it's rendered.
        if (isset($pick)) {
            $pick->line->output = $tableHtml;
        }
    }

    /**
     * Prepare HTML attributes string from the cell attributes.
     * @param array $attributes
     * @return string
     */
    protected function prepareAttributes($attributes)
    {
        $attributesString = '';
        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, ['rowspan', 'colspan', 'row', 'cell'])) { // Filter attributes
                $attributesString .= " {$attribute}=\"{$value}\"";
            }
        }
        return $attributesString;
    }
}
