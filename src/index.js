import { registerPlugin } from '@wordpress/plugins';
import render from './components/Sidebar';

registerPlugin(
    'preview-by-site',
    {
        icon: 'visibility',
        render,
    }
);