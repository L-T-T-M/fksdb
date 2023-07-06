import ActionsStoreCreator from 'vendor/fykosak/nette-frontend-component/src/Components/ActionsStoreCreator';
import { NetteActions } from 'vendor/fykosak/nette-frontend-component/src/NetteActions/netteActions';
import { SubmitModel } from 'FKSDB/Models/ORM/Models/submit-model';
import * as React from 'react';
import UploadContainer from './Components/container';
import { app, Store } from './Reducers';
import './style.scss';
import { TranslatorContext } from '@translator/context';
import { availableLanguage, Translator } from '@translator/translator';

interface Props {
    data: SubmitModel;
    actions: NetteActions;
    translator: Translator<availableLanguage>;
}

export default function AjaxSubmitComponent(props: Props) {
    return <ActionsStoreCreator<Store, SubmitModel>
        initialData={{
            actions: props.actions,
            data: props.data,
            messages: [],
        }}
        app={app}
    >
        <TranslatorContext.Provider value={props.translator}>
            <UploadContainer/>
        </TranslatorContext.Provider>
    </ActionsStoreCreator>;
}
