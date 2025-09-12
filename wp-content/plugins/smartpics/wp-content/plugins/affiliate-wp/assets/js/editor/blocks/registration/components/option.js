import { Button } from '@wordpress/components';
import { Component, createRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

class AffiliateWPOption extends Component {
	constructor( ...args ) {
		super( ...args );
		this.onChangeOption = this.onChangeOption.bind( this );
		this.onKeyPress = this.onKeyPress.bind( this );
		this.onDeleteOption = this.onDeleteOption.bind( this );
		this.textInput = createRef();
	}

	componentDidMount() {
		if ( this.props.isInFocus ) {
			this.textInput.current.focus();
		}
	}

	componentDidUpdate() {
		if ( this.props.isInFocus ) {
			this.textInput.current.focus();
		}
	}

	onChangeOption( event ) {
		this.props.onChangeOption( this.props.index, event.target.value );
	}

	onKeyPress( event ) {
		if ( event.key === 'Enter' ) {
			this.props.onAddOption( this.props.index );
			event.preventDefault();
			return;
		}

		if ( event.key === 'Backspace' && event.target.value === '' ) {
			this.props.onChangeOption( this.props.index );
			event.preventDefault();
		}
	}

	onDeleteOption() {
		this.props.onChangeOption( this.props.index );
	}

	render() {
		const { isSelected, option, type } = this.props;
		return (
			<li className="affiliatewp-option">
				{ type && type !== 'select' && (
					<input className="affiliatewp-option__type" type={ type } disabled />
				) }
				<input
					type="text"
					className="affiliatewp-option__input"
					value={ option }
					placeholder={ __( 'Write option…', 'affiliate-wp' ) }
					onChange={ this.onChangeOption }
					onKeyDown={ this.onKeyPress }
					ref={ this.textInput }
				/>
				{ isSelected && (
					<Button
						className="affiliatewp-option__remove"
						icon="trash"
						label={ __( 'Remove option', 'affiliate-wp' ) }
						onClick={ this.onDeleteOption }
					/>
				) }
			</li>
		);
	}
}

export default AffiliateWPOption;
