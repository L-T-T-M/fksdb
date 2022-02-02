import { translator } from '@translator/translator';
import * as React from 'react';
import { connect } from 'react-redux';
import {
    Action,
    Dispatch,
} from 'redux';
import { setNewState } from '../actions';
import { State } from 'FKSDB/Components/Controls/Fyziklani/ResultsAndStatistics/Statistics/Reducers/stats';

interface StateProps {
    onSetNewState(data: State): void;
}

class Legend extends React.Component<StateProps> {

    public render() {
        const availablePoints = [1, 2, 3, 5];
        const {onSetNewState} = this.props;
        const legend = availablePoints.map((points: number) => {
            let pointsLabel;
            switch (points) {
                case 1:
                    pointsLabel = translator.getText('bod');
                    break;
                case 2:
                case 3:
                    pointsLabel = translator.getText('body');
                    break;
                default:
                    pointsLabel = translator.getText('bodů');
            }
            return (<div key={points}
                         className="col-12 legend-item"
                         onMouseEnter={() => {
                             onSetNewState({activePoints: +points})
                         }}
                         onMouseLeave={() => {
                             onSetNewState({activePoints: null})
                         }}>
                <i className="icon" data-points={points}/>
                <strong>{points + ' ' + pointsLabel}</strong>
            </div>);
        });

        return (
            <div className={'legend fyziklani-legend align-content-center col-lg-4 d-flex flex-wrap'}>
                {legend}
            </div>
        );
    }
}

const mapDispatchToProps = (dispatch: Dispatch<Action<string>>): StateProps => {
    return {
        onSetNewState: data => dispatch(setNewState(data)),
    };
};

export default connect(null, mapDispatchToProps)(Legend);
