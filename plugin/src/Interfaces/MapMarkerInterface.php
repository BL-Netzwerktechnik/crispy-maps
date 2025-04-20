<?php

namespace blfilme\lostplaces\Interfaces;

use blfilme\lostplaces\Models\PointModel;

interface MapMarkerInterface
{
    public function getIconUrl(): string;
    public function getIconSize(): PointModel;
    public function getIconAnchor(): PointModel;
    public function getPopupAnchor(): PointModel;
    public function getShadowUrl(): ?string;
    public function getShadowSize(): ?PointModel;
    public function getShadowAnchor(): ?PointModel;
}