<?php

namespace blfilme\lostplaces\Models;

class MapMarkerModel 
{
    public function __construct(
        private string $iconUrl,
        private PointModel $iconSize,
        private PointModel $iconAnchor,
        private PointModel $popupAnchor,
        private ?string $shadowUrl = null,
        private ?PointModel $shadowSize = null,
        private ?PointModel $shadowAnchor = null,
    )
    {
        
    }

    public function getIconUrl(): string
    {
        return $this->iconUrl;
    }

    public function getIconSize(): PointModel
    {
        return $this->iconSize;
    }

    public function getIconAnchor(): PointModel
    {
        return $this->iconAnchor;
    }

    public function getPopupAnchor(): PointModel
    {
        return $this->popupAnchor;
    }

    public function getShadowUrl(): ?string
    {
        return $this->shadowUrl;
    }

    public function getShadowSize(): ?PointModel
    {
        return $this->shadowSize;
    }

    public function getShadowAnchor(): ?PointModel
    {
        return $this->shadowAnchor;
    }
    public function toArray(): array
    {
        return [
            'iconUrl' => $this->iconUrl,
            'iconSize' => $this->iconSize->toArray(),
            'iconAnchor' => $this->iconAnchor->toArray(),
            'popupAnchor' => $this->popupAnchor->toArray(),
            'shadowUrl' => $this->shadowUrl,
            'shadowSize' => $this->shadowSize ? $this->shadowSize->toArray() : null,
            'shadowAnchor' => $this->shadowAnchor ? $this->shadowAnchor->toArray() : null,
        ];
    }
}