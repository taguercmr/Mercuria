import TextInput from '@/Components/TextInput';
import { useState } from 'react';
import type { InputHTMLAttributes } from 'react';

type Props = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> & {
    visibilityLabel?: string;
};

export default function PasswordInput({
    visibilityLabel = 'le mot de passe',
    ...props
}: Props) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="ma-password">
            <TextInput {...props} type={visible ? 'text' : 'password'} />
            <button
                type="button"
                className="ma-password-toggle"
                aria-label={`${visible ? 'Masquer' : 'Afficher'} ${visibilityLabel}`}
                aria-pressed={visible}
                aria-controls={props.id}
                onClick={() => setVisible(!visible)}
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                    <circle cx="12" cy="12" r="3" />
                    {visible && <path d="m3 3 18 18" />}
                </svg>
            </button>
        </div>
    );
}
