<?php
// @phpcsSniff SymPress.Classes.AccessorNaming

interface WithAccessorNamingInterface
{
    // @phpcsWarningOnNextLine GetterRequired
    public function title(): string;

    public function getTitle(): string;

    // @phpcsWarningOnNextLine GetterRequired
    public function active(): bool;

    public function isActive(): bool;

    // @phpcsWarningOnNextLine SetterRequired
    public function withTitle(string $title): self;

    public function buildTitle(): string;
}

final class WithAccessorNaming
{
    public string $hookedTitle {
        get => $this->hookedTitle;
        set {
            $this->hookedTitle = trim($value);
        }
    }

    private string $title = '';

    private bool $active = false;

    // @phpcsWarningOnNextLine GetterRequired
    public function title(): string
    {
        return $this->title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    // @phpcsWarningOnNextLine GetterRequired
    public function active(): bool
    {
        return $this->active;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    // @phpcsWarningOnNextLine SetterRequired
    public function rename(string $title): void
    {
        $this->title = $title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function buildTitle(): string
    {
        return strtoupper($this->title);
    }

    public function titleOrDefault(): string
    {
        if ($this->title === '') {
            return 'Default';
        }

        return $this->title;
    }

    public function renameAndNotify(string $title): void
    {
        $this->title = $title;

        $this->notifyTitleChanged();
    }

    private function titleInternally(): string
    {
        return $this->title;
    }

    private function notifyTitleChanged(): void
    {
    }
}
