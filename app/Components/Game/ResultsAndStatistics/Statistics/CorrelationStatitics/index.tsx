import { TeamModel } from 'FKSDB/Models/ORM/Models/Fyziklani/team-model';
import * as React from 'react';
import { useContext } from 'react';
import { connect } from 'react-redux';
import { Action, Dispatch } from 'redux';
import { setNewState } from '../../actions/stats';
import GlobalCorrelation from './global-correlation';
import { Store } from 'FKSDB/Components/Game/ResultsAndStatistics/reducers/store';
import { TranslatorContext } from '@translator/context';

interface StateProps {
    teams: TeamModel[];
    firstTeamId: number;
    secondTeamId: number;
}

interface DispatchProps {

    onChangeFirstTeam(id: number): void;

    onChangeSecondTeam(id: number): void;
}

function CorrelationStats(props: StateProps & DispatchProps) {

    const translator = useContext(TranslatorContext);
    const {teams, onChangeFirstTeam, onChangeSecondTeam, firstTeamId, secondTeamId} = props;
    const teamsOptions = teams.map((team) => {
        return <option key={team.teamId} value={team.teamId}
        >{team.name}</option>;
    });

    const teamSelect = (
        <div className="row">
            <div className="col-6">
                <select
                    className="form-control"
                    onChange={(event) => {
                        onChangeFirstTeam(+event.target.value);
                    }}
                    value={props.firstTeamId}
                >
                    <option value={null}>--{translator.getText('select team')}--</option>
                    {teamsOptions}
                </select>
            </div>
            <div className="col-6">
                <select
                    className="form-control"
                    onChange={(event) => {
                        onChangeSecondTeam(+event.target.value);
                    }} value={props.secondTeamId}
                >
                    <option value={null}>--{translator.getText('select team')}--</option>
                    {teamsOptions}
                </select>
            </div>
        </div>
    );
    const firstSelectedTeam = teams.filter((team) => {
        return team.teamId === firstTeamId;
    })[0];

    const secondSelectedTeam = teams.filter((team) => {
        return team.teamId === secondTeamId;
    })[0];

    const headline = (
        <h2>{translator.getText('Correlation ') +
            ((firstSelectedTeam && secondSelectedTeam) ? (firstSelectedTeam.name + ' VS ' + secondSelectedTeam.name) : '')}</h2>
    );

    return <>
        {headline}
        {teamSelect}
        {(firstTeamId && secondTeamId) ? /*<Table/>*/null : <GlobalCorrelation/>}
    </>;
}

const mapStateToProps = (state: Store): StateProps => {
    return {
        firstTeamId: state.statistics.firstTeamId,
        secondTeamId: state.statistics.secondTeamId,
        teams: state.data.teams,
    };
};

const mapDispatchToProps = (dispatch: Dispatch<Action<string>>): DispatchProps => {
    return {
        onChangeFirstTeam: (teamId) => dispatch(setNewState({firstTeamId: +teamId})),
        onChangeSecondTeam: (teamId) => dispatch(setNewState({secondTeamId: +teamId})),
    };
};

export default connect(mapStateToProps, mapDispatchToProps)(CorrelationStats);
